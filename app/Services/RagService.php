<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Facades\DB;

class RagService
{
    private AnthropicService $claude;
    private int $topK = 5;

    public function __construct(AnthropicService $claude)
    {
        $this->claude = $claude;
    }

    /**
     * Full RAG pipeline: retrieve relevant chunks → generate answer with Claude.
     */
    public function answer(string $question, int $topK = null): array
    {
        $topK = $topK ?? $this->topK;

        $relevantChunks = $this->retrieveTopK($question, $topK);

        if ($relevantChunks->isEmpty()) {
            return [
                'answer'  => 'No relevant documents found. Please upload some documents first.',
                'sources' => [],
            ];
        }

        $context = $relevantChunks->map(function ($chunk) {
            return "[{$chunk->document->title}]\n{$chunk->content}";
        })->implode("\n\n---\n\n");

        $systemPrompt = "You are a helpful assistant. Answer the user's question using ONLY the provided context. "
            . "If the answer is not in the context, say so clearly. Do not make up information.\n\n"
            . "Context:\n{$context}";

        $answer = $this->claude->chat($systemPrompt, $question);

        $sources = $relevantChunks->map(function ($chunk) {
            return [
                'document_id'    => $chunk->document_id,
                'document_title' => $chunk->document->title,
                'chunk_index'    => $chunk->chunk_index,
                'excerpt'        => mb_substr($chunk->content, 0, 150) . '...',
                'score'          => round((float) ($chunk->relevance_score ?? 0), 4),
            ];
        })->values()->toArray();

        return [
            'answer'  => $answer,
            'sources' => $sources,
        ];
    }

    /**
     * Use MySQL FULLTEXT (BM25) to retrieve the top-K matching chunks.
     */
    private function retrieveTopK(string $query, int $k)
    {
        // Sanitize query for MATCH AGAINST boolean mode
        $safeQuery = $this->sanitizeFulltextQuery($query);

        $chunks = DocumentChunk::with('document')
            ->selectRaw('document_chunks.*, MATCH(content) AGAINST(? IN BOOLEAN MODE) AS relevance_score', [$safeQuery])
            ->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$safeQuery])
            ->orderByDesc('relevance_score')
            ->limit($k)
            ->get();

        // Fallback: if no FULLTEXT hits (very short / stopword-only query), return most recent chunks
        if ($chunks->isEmpty()) {
            $chunks = DocumentChunk::with('document')
                ->latest()
                ->limit($k)
                ->get()
                ->each(fn($c) => $c->relevance_score = 0);
        }

        return $chunks;
    }

    /**
     * Strip special FULLTEXT characters to avoid syntax errors.
     */
    private function sanitizeFulltextQuery(string $query): string
    {
        // Remove boolean mode operators that could break the query
        $clean = preg_replace('/[+\-><\(\)~*"@]+/', ' ', $query);
        $words = preg_split('/\s+/', trim($clean), -1, PREG_SPLIT_NO_EMPTY);

        // Prefix each word with + so all terms are required (AND mode)
        return implode(' ', array_map(fn($w) => '+' . $w, $words));
    }
}
