<?php
/**
 * Ollama (Local LLM) Provider
 * 
 * @package CurlyCMS\Core\AI
 */

declare(strict_types=1);

namespace CurlyCMS\Core\AI;

class OllamaProvider implements AIProvider
{
    private string $host;
    private string $model;
    private int $timeout;
    private string $apiUrl;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? getenv('OLLAMA_HOST') ?? 'http://localhost:11434';
        $this->model = $config['model'] ?? getenv('OLLAMA_MODEL') ?? 'llama2';
        $this->timeout = (int) ($config['timeout'] ?? 120);
        $this->apiUrl = rtrim($this->host, '/') . '/api/generate';
    }

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'prompt' => $fullPrompt,
            'stream' => false,
            'options' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? 4096
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => $this->timeout
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Ollama API error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException('Ollama API error: HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);

        if (!isset($data['response'])) {
            throw new \RuntimeException('Invalid Ollama response structure');
        }

        return [
            'content' => $data['response'],
            'tokens' => ($data['prompt_eval_count'] ?? 0) + ($data['eval_count'] ?? 0),
            'prompt_tokens' => $data['prompt_eval_count'] ?? 0,
            'completion_tokens' => $data['eval_count'] ?? 0,
            'model' => $data['model'] ?? $this->model,
            'duration' => $data['total_duration'] ?? 0
        ];
    }

    public function isAvailable(): bool
    {
        try {
            $ch = curl_init(rtrim($this->host, '/') . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5
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
        try {
            $ch = curl_init(rtrim($this->host, '/') . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $models = [];

            if (!empty($data['models'])) {
                foreach ($data['models'] as $model) {
                    $models[$model['name']] = $model['name'];
                }
            }

            return $models;
        } catch (\Throwable $e) {
            return [
                'llama2' => 'Llama 2',
                'llama3' => 'Llama 3',
                'mistral' => 'Mistral',
                'codellama' => 'Code Llama',
                'phi' => 'Phi'
            ];
        }
    }
}
