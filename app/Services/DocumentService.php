<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentService
{
    private int $chunkSize    = 1500;
    private int $chunkOverlap = 200;

    public function ingestFile(UploadedFile $file): Document
    {
        if (strtolower($file->getClientOriginalExtension()) === 'pdf') {
            $parser  = new PdfParser();
            $pdf     = $parser->parseFile($file->getRealPath());
            $content = $pdf->getText();
        } else {
            $content = file_get_contents($file->getRealPath());
        }

        $content = $this->normalizeWhitespace($content);

        $document = Document::create([
            'title'             => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_filename' => $file->getClientOriginalName(),
            'content'           => $content,
        ]);

        $this->processChunks($document, $content);

        return $document;
    }

    public function ingestText(string $title, string $content): Document
    {
        $content = $this->normalizeWhitespace($content);

        $document = Document::create([
            'title'   => $title,
            'content' => $content,
        ]);

        $this->processChunks($document, $content);

        return $document;
    }

    private function processChunks(Document $document, string $content): void
    {
        $chunks = $this->splitIntoChunks($content);

        foreach ($chunks as $index => $chunkText) {
            DocumentChunk::create([
                'document_id' => $document->id,
                'chunk_index' => $index,
                'content'     => $chunkText,
            ]);
        }

        $document->update(['chunk_count' => count($chunks)]);
    }

    private function splitIntoChunks(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks    = [];
        $current   = '';

        foreach ($sentences as $sentence) {
            if (strlen($current) + strlen($sentence) + 1 > $this->chunkSize && $current !== '') {
                $chunks[] = trim($current);
                $words    = explode(' ', $current);
                $overlap  = array_slice($words, -(int) max(1, $this->chunkOverlap / 5));
                $current  = implode(' ', $overlap) . ' ' . $sentence;
            } else {
                $current .= ($current ? ' ' : '') . $sentence;
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return $chunks ?: [substr($text, 0, $this->chunkSize)];
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function ingestDatabaseQuery(string $title, string $sql, array $connection): Document
    {
        $result  = $this->runDbQuery($sql, $connection);
        $content = $this->formatQueryResults($title, $sql, $result);
        $content = $this->normalizeWhitespace($content);

        $document = Document::create([
            'title'   => $title,
            'content' => $content,
        ]);

        $this->processChunks($document, $content);

        return $document;
    }

    public function runDbQuery(string $sql, array $connection, ?int $limit = null): array
    {
        $this->validateSelectQuery($sql);

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $connection['host'],
            $connection['port'] ?? 3306,
            $connection['database']
        );

        $pdo = new \PDO($dsn, $connection['username'], $connection['password'], [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT            => 10,
        ]);

        $fetchSql = $limit ? $this->applyLimit($sql, $limit) : $sql;
        $stmt     = $pdo->prepare($fetchSql);
        $stmt->execute();
        $rows    = $stmt->fetchAll();
        $columns = $rows ? array_keys($rows[0]) : [];

        // Count total without LIMIT
        try {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$sql}) AS _cnt");
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();
        } catch (\Throwable) {
            $total = count($rows);
        }

        return compact('columns', 'rows', 'total');
    }

    private function validateSelectQuery(string $sql): void
    {
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $sql)));

        if (!str_starts_with($upper, 'SELECT')) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed.');
        }

        foreach (['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE', 'REPLACE', 'EXEC'] as $kw) {
            if (preg_match('/\b' . $kw . '\b/', $upper)) {
                throw new \InvalidArgumentException("Forbidden keyword in query: {$kw}.");
            }
        }
    }

    private function applyLimit(string $sql, int $limit): string
    {
        if (preg_match('/\bLIMIT\b/i', $sql)) {
            return $sql;
        }
        return rtrim(trim($sql), ';') . " LIMIT {$limit}";
    }

    private function formatQueryResults(string $title, string $sql, array $result): string
    {
        $lines   = [];
        $lines[] = "Title: {$title}.";
        $lines[] = "Source query: {$sql}.";
        $lines[] = "Total records: {$result['total']}.";
        $lines[] = "Columns: " . implode(', ', $result['columns']) . ".";

        foreach ($result['rows'] as $i => $row) {
            $parts = [];
            foreach ($row as $col => $val) {
                $parts[] = "{$col}: " . (is_null($val) ? 'NULL' : $val);
            }
            $lines[] = 'Record ' . ($i + 1) . ': ' . implode(', ', $parts) . '.';
        }

        return implode(' ', $lines);
    }

    public function deleteDocument(Document $document): void
    {
        $document->delete();
    }
}
