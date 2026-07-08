<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private Client $client;
    private string $apiKey;
    private string $embeddingModel;
    private string $chatModel;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->embeddingModel = config('openai.embedding_model', 'text-embedding-3-small');
        $this->chatModel = config('openai.chat_model', 'gpt-4o-mini');

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 60,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Generate an embedding vector for the given text.
     * Returns a float array of 1536 dimensions.
     */
    public function embed(string $text): array
    {
        $response = $this->client->post('embeddings', [
            'json' => [
                'input' => $text,
                'model' => $this->embeddingModel,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['data'][0]['embedding'];
    }

    /**
     * Generate embeddings for multiple texts in one API call.
     */
    public function embedBatch(array $texts): array
    {
        $response = $this->client->post('embeddings', [
            'json' => [
                'input' => $texts,
                'model' => $this->embeddingModel,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $embeddings = [];
        foreach ($data['data'] as $item) {
            $embeddings[$item['index']] = $item['embedding'];
        }
        ksort($embeddings);

        return array_values($embeddings);
    }

    /**
     * Generate a chat completion using the provided messages.
     */
    public function chat(array $messages, int $maxTokens = 1000, float $temperature = 0.7): string
    {
        $response = $this->client->post('chat/completions', [
            'json' => [
                'model'       => $this->chatModel,
                'messages'    => $messages,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['choices'][0]['message']['content'];
    }
}
