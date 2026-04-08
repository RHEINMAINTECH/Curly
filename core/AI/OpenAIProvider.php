<?php
/**
 * OpenAI Provider
 * 
 * @package CurlyCMS\Core\AI
 */

declare(strict_types=1);

namespace CurlyCMS\Core\AI;

class OpenAIProvider implements AIProvider
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $timeout;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? getenv('AI_API_KEY') ?? '';
        $this->model = $config['model'] ?? getenv('AI_MODEL') ?? 'gpt-4';
        $this->maxTokens = (int) ($config['max_tokens'] ?? getenv('AI_MAX_TOKENS') ?? 4096);
        $this->temperature = (float) ($config['temperature'] ?? getenv('AI_TEMPERATURE') ?? 0.7);
        $this->timeout = (int) ($config['timeout'] ?? 60);
    }

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        // Add conversation history if provided
        if (!empty($options['history'])) {
            $historyMessages = [];
            foreach ($options['history'] as $msg) {
                $historyMessages[] = [
                    'role' => $msg['role'] ?? 'user',
                    'content' => $msg['content']
                ];
            }
            $messages = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $historyMessages,
                [['role' => 'user', 'content' => $userPrompt]]
            );
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature
        ];

        if (!empty($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        if (!empty($options['functions'])) {
            $payload['functions'] = $options['functions'];
        }

        if (!empty($options['function_call'])) {
            $payload['function_call'] = $options['function_call'];
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => $this->timeout
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('OpenAI API error: ' . $error);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('OpenAI API error: ' . $errorMessage);
        }

        $data = json_decode($response, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Invalid OpenAI response structure');
        }

        return [
            'content' => $data['choices'][0]['message']['content'],
            'tokens' => $data['usage']['total_tokens'] ?? 0,
            'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            'model' => $data['model'] ?? $this->model,
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null
        ];
    }

    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            $ch = curl_init('https://api.openai.com/v1/models');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey
                ],
                CURLOPT_TIMEOUT => 10
            ]);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getModels(): array
    {
        return [
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo Preview',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K'
        ];
    }
}
