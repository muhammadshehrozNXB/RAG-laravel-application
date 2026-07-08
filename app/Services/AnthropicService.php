<?php

namespace App\Services;

use GuzzleHttp\Client;

class AnthropicService
{
    private Client $client;
    private string $model;

    public function __construct()
    {
        $this->model = config('anthropic.model', 'claude-haiku-4-5-20251001');

        $this->client = new Client([
            'base_uri' => 'https://api.anthropic.com/v1/',
            'timeout'  => 120,
            'headers'  => [
                'x-api-key'         => config('anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
        ]);
    }

    /**
     * Generate a response from Claude given a system prompt and user message.
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): string
    {
        $response = $this->client->post('messages', [
            'json' => [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['content'][0]['text'];
    }
}
