<?php

namespace App\Services;

class DatabaseQueryService
{
    public function __construct(private AnthropicService $claude) {}

    /**
     * Full Text-to-SQL pipeline:
     *  1. Discover schema (all tables + columns)
     *  2. Ask Claude to generate a SELECT query
     *  3. Execute the query
     *  4. Ask Claude to summarise the results in plain English
     */
    public function ask(string $question, array $connection): array
    {
        $pdo    = $this->connect($connection);
        $schema = $this->discoverSchema($pdo);

        if (empty($schema)) {
            return [
                'answer'  => 'The connected database has no tables.',
                'sql'     => null,
                'columns' => [],
                'rows'    => [],
                'total'   => 0,
                'error'   => null,
            ];
        }

        $sql = $this->generateSql($question, $schema);

        try {
            ['columns' => $columns, 'rows' => $rows, 'total' => $total] =
                $this->executeQuery($pdo, $sql);
        } catch (\Throwable $e) {
            // Ask Claude to self-correct once
            $sql = $this->generateSql($question, $schema, $e->getMessage());
            ['columns' => $columns, 'rows' => $rows, 'total' => $total] =
                $this->executeQuery($pdo, $sql);
        }

        $answer = $this->summariseResults($question, $sql, $columns, $rows, $total);

        return compact('answer', 'sql', 'columns', 'rows', 'total') + ['error' => null];
    }

    // ------------------------------------------------------------------
    // Schema discovery
    // ------------------------------------------------------------------

    private function discoverSchema(\PDO $pdo): array
    {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $schema = [];

        foreach ($tables as $table) {
            $cols = $pdo->query("DESCRIBE `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            $schema[$table] = array_map(fn($c) => [
                'column' => $c['Field'],
                'type'   => $c['Type'],
                'null'   => $c['Null'],
                'key'    => $c['Key'],
            ], $cols);
        }

        return $schema;
    }

    private function schemaToText(array $schema): string
    {
        $lines = [];
        foreach ($schema as $table => $cols) {
            $colDefs = array_map(
                fn($c) => "{$c['column']} ({$c['type']})" . ($c['key'] === 'PRI' ? ' PK' : ''),
                $cols
            );
            $lines[] = "Table `{$table}`: " . implode(', ', $colDefs);
        }
        return implode("\n", $lines);
    }

    // ------------------------------------------------------------------
    // SQL generation via Claude
    // ------------------------------------------------------------------

    private function generateSql(string $question, array $schema, ?string $previousError = null): string
    {
        $schemaText = $this->schemaToText($schema);

        $system = <<<PROMPT
You are a MySQL expert. Given a database schema, write a single valid MySQL SELECT query that answers the user's question.

Rules:
- Output ONLY the raw SQL query — no markdown, no explanation, no code fences.
- Use only SELECT statements. Never use DROP, DELETE, UPDATE, INSERT, ALTER, TRUNCATE.
- Use backticks around table and column names.
- If the question is ambiguous, make a reasonable assumption.
- Limit results to 200 rows maximum using LIMIT unless the user asks for all records.

Database schema:
{$schemaText}
PROMPT;

        $userMsg = $previousError
            ? "Previous attempt failed with: {$previousError}\n\nOriginal question: {$question}\n\nWrite a corrected SQL query."
            : $question;

        $raw = trim($this->claude->chat($system, $userMsg, 512));

        // Strip accidental markdown fences
        $raw = preg_replace('/^```(?:sql)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);

        return trim($raw);
    }

    // ------------------------------------------------------------------
    // Query execution
    // ------------------------------------------------------------------

    private function executeQuery(\PDO $pdo, string $sql): array
    {
        $this->guardSelectOnly($sql);

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows    = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $columns = $rows ? array_keys($rows[0]) : [];

        try {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS _c");
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();
        } catch (\Throwable) {
            $total = count($rows);
        }

        return compact('columns', 'rows', 'total');
    }

    private function guardSelectOnly(string $sql): void
    {
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $sql)));

        if (!str_starts_with($upper, 'SELECT')) {
            throw new \RuntimeException('Only SELECT queries are allowed.');
        }

        foreach (['DROP','DELETE','UPDATE','INSERT','ALTER','CREATE','TRUNCATE','REPLACE','EXEC'] as $kw) {
            if (preg_match('/\b' . $kw . '\b/', $upper)) {
                throw new \RuntimeException("Forbidden keyword in generated query: {$kw}.");
            }
        }
    }

    // ------------------------------------------------------------------
    // Result summarisation
    // ------------------------------------------------------------------

    private function summariseResults(
        string $question,
        string $sql,
        array  $columns,
        array  $rows,
        int    $total
    ): string {
        if (empty($rows)) {
            return 'No records matched your query.';
        }

        // Build a compact text snapshot of the results (cap at 50 rows to stay within token budget)
        $preview = array_slice($rows, 0, 50);
        $lines   = [implode(' | ', $columns)];
        foreach ($preview as $row) {
            $lines[] = implode(' | ', array_map(fn($v) => $v ?? 'NULL', array_values($row)));
        }
        $resultText = implode("\n", $lines);
        $suffix     = $total > 50 ? "\n(showing 50 of {$total} total rows)" : '';

        $system = 'You are a helpful data analyst. Answer the user\'s question in clear, concise plain English '
            . 'based on the query results below. Include key numbers or names from the data. '
            . 'Do not repeat the SQL. Do not use bullet points unless there are multiple distinct items.';

        $userMsg = "Question: {$question}\n\nSQL used:\n{$sql}\n\nResults:\n{$resultText}{$suffix}";

        return $this->claude->chat($system, $userMsg, 1024);
    }

    // ------------------------------------------------------------------
    // PDO connection
    // ------------------------------------------------------------------

    public function connect(array $connection): \PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $connection['host'],
            $connection['port'] ?? 3306,
            $connection['database']
        );

        return new \PDO($dsn, $connection['username'], $connection['password'], [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT            => 15,
        ]);
    }
}
