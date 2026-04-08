<?php
/**
 * Anthropic (Claude) Provider
 * 
 * @package CurlyCMS\Core\AI
 */

declare(strict_types=1);

namespace CurlyCMS\Core\AI;

class AnthropicProvider implements AIProvider
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $timeout;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? getenv('ANTHROPIC_API_KEY') ?? '';
        $this->model = $config['model'] ?? 'claude-3-opus-20240229';
        $this->maxTokens = (int) ($config['max_tokens'] ?? 4096);
        $this->temperature = (float) ($config['temperature'] ?? 0.7);
        $this->timeout = (int) ($config['timeout'] ?? 120);
    }

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        $messages = [
            ['role' => 'user', 'content' => $userPrompt]
        ];

        // Add conversation history if provided
        if (!empty($options['history'])) {
            $messages = [];
            foreach ($options['history'] as $msg) {
                $messages[] = [
                    'role' => $msg['role'] ?? 'user',
                    'content' => $msg['content']
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $userPrompt];
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'system' => $systemPrompt,
            'messages' => $messages
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => $this->timeout
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Anthropic API error: ' . $error);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('Anthropic API error: ' . $errorMessage);
        }

        $data = json_decode($response, true);

        if (!isset($data['content'][0]['text'])) {
            throw new \RuntimeException('Invalid Anthropic response structure');
        }

        return [
            'content' => $data['content'][0]['text'],
            'tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
            'model' => $data['model'] ?? $this->model,
            'stop_reason' => $data['stop_reason'] ?? null
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getModels(): array
    {
        return [
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-2.1' => 'Claude 2.1',
            'claude-2.0' => 'Claude 2.0'
        ];
    }
}
