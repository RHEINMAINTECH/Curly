<?php
/**
 * AI Service - Main AI Integration
 * 
 * @package CurlyCMS\Core\AI
 */

declare(strict_types=1);

namespace CurlyCMS\Core\AI;

use CurlyCMS\Core\App;
use CurlyCMS\Core\Database;

class AIService
{
    private array $config;
    private Database $db;
    private ?AIProvider $provider = null;
    private array $context = [];

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'provider' => 'openai',
            'model' => 'gpt-4',
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'timeout' => 60
        ], $config);
        
        $this->db = App::getInstance()->getDatabase();
        $this->initProvider();
    }

    private function initProvider(): void
    {
        $provider = $this->config['provider'] ?? 'openai';
        
        switch ($provider) {
            case 'openai':
                $this->provider = new OpenAIProvider($this->config);
                break;
            case 'anthropic':
                $this->provider = new AnthropicProvider($this->config);
                break;
            case 'ollama':
                $this->provider = new OllamaProvider($this->config);
                break;
            default:
                throw new \RuntimeException("Unknown AI provider: {$provider}");
        }
    }

    public function generate(string $prompt, array $options = []): ?string
    {
        $startTime = microtime(true);
        
        $systemPrompt = $this->buildSystemPrompt($options);
        $fullPrompt = $this->buildPrompt($prompt, $options);
        
        try {
            $response = $this->provider->generate($systemPrompt, $fullPrompt, $options);
            
            // Log usage
            $this->logUsage(
                $prompt,
                $response['content'],
                $response['tokens'] ?? 0,
                microtime(true) - $startTime
            );
            
            return $response['content'];
        } catch (\Throwable $e) {
            $this->logError($prompt, $e->getMessage());
            return null;
        }
    }

    public function generateStructure(string $type, string $description): ?array
    {
        $templates = [
            'page' => 'Generate a JSON structure for a web page with Bootstrap 5 layout. Include container, rows, columns, and components.',
            'post' => 'Generate a JSON structure for a blog post with title, content, metadata, and structured data.',
            'layout' => 'Generate a JSON structure for a page layout with header, footer, sidebar, and main content area.',
            'component' => 'Generate a JSON structure for a reusable UI component.',
            'menu' => 'Generate a JSON structure for a navigation menu with hierarchical items.',
            'form' => 'Generate a JSON structure for a form with fields, validation rules, and actions.',
            'seo' => 'Generate a JSON structure for SEO metadata including title, description, keywords, and social tags.'
        ];
        
        $template = $templates[$type] ?? 'Generate a JSON structure.';
        
        $prompt = <<<PROMPT
{$template}

Description: {$description}

Requirements:
1. Output must be valid JSON only, no markdown code blocks
2. Use Bootstrap 5 naming conventions for layout classes
3. Include all necessary attributes and content
4. Structure should be machine-readable and AI-editable

Output the JSON structure:
PROMPT;

        $response = $this->generate($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 4000
        ]);
        
        if (!$response) {
            return null;
        }
        
        // Parse JSON from response
        $json = $this->extractJson($response);
        
        if ($json === null) {
            return null;
        }
        
        return $json;
    }

    public function optimizeSEO(string $content, array $keywords = []): ?string
    {
        $keywordList = implode(', ', $keywords);
        
        $prompt = <<<PROMPT
Optimize the following content for SEO while maintaining readability and natural flow.

Target Keywords: {$keywordList}

Content to optimize:
{$content}

Requirements:
1. Naturally incorporate keywords
2. Improve meta descriptions and headings if present
3. Maintain original meaning and tone
4. Follow SEO best practices
5. Output optimized content only

Optimized content:
PROMPT;

        return $this->generate($prompt, ['temperature' => 0.4]);
    }

    public function generateMetaTitle(string $content, array $keywords = []): ?string
    {
        $prompt = <<<PROMPT
Generate an SEO-optimized meta title for the following content.

Target Keywords: {implode(', ', $keywords)}

Content:
{$content}

Requirements:
1. Maximum 60 characters
2. Include primary keyword naturally
3. Compelling and click-worthy
4. Output only the title text

Meta title:
PROMPT;

        return $this->generate($prompt, ['temperature' => 0.5, 'max_tokens' => 100]);
    }

    public function generateMetaDescription(string $content, array $keywords = []): ?string
    {
        $keywordList = implode(', ', $keywords);
        
        $prompt = <<<PROMPT
Generate an SEO-optimized meta description for the following content.

Target Keywords: {$keywordList}

Content:
{$content}

Requirements:
1. Maximum 160 characters
2. Include primary keywords naturally
3. Compelling and action-oriented
4. Accurately summarize content
5. Output only the description text

Meta description:
PROMPT;

        return $this->generate($prompt, ['temperature' => 0.5, 'max_tokens' => 200]);
    }

    public function generateExcerpt(string $content, int $length = 150): ?string
    {
        $prompt = <<<PROMPT
Generate a compelling excerpt/summary for the following content.

Maximum length: {$length} characters

Content:
{$content}

Requirements:
1. Maximum {$length} characters
2. Capture main points
3. Engaging and informative
4. Output only the excerpt text

Excerpt:
PROMPT;

        return $this->generate($prompt, ['temperature' => 0.4, 'max_tokens' => 300]);
    }

    public function generateAltText(string $imageContext, string $pageContext = ''): ?string
    {
        $prompt = <<<PROMPT
Generate descriptive alt text for an image.

Image Context: {$imageContext}
Page Context: {$pageContext}

Requirements:
1. Maximum 125 characters
2. Describe the image for accessibility
3. Include relevant keywords naturally
4. Be specific and descriptive
5. Output only the alt text

Alt text:
PROMPT;

        return $this->generate($prompt, ['temperature' => 0.4, 'max_tokens' => 100]);
    }

    public function translateContent(string $content, string $targetLanguage): ?string
    {
        $prompt = <<<PROMPT
Translate the following content to {$targetLanguage}.

Content:
{$content}

Requirements:
1. Maintain formatting and structure
2. Preserve HTML tags if present
3. Natural, native-quality translation
4. Output only the translated content

Translation:
PROMPT;

        return $this->generate($prompt, ['temperature' => 0.3]);
    }

    public function analyzeContent(string $content): ?array
    {
        $prompt = <<<PROMPT
Analyze the following content and provide insights in JSON format.

Content:
{$content}

Provide analysis as JSON with these fields:
- sentiment: positive/negative/neutral
- readability_score: estimated Flesch reading ease (0-100)
- word_count: number of words
- key_topics: array of main topics
- suggestions: array of improvement suggestions
- tone: formal/casual/technical/etc

Output only valid JSON:
PROMPT;

        $response = $this->generate($prompt, ['temperature' => 0.3]);
        
        if (!$response) {
            return null;
        }
        
        return $this->extractJson($response);
    }

    public function generateContentIdeas(string $topic, int $count = 10): ?array
    {
        $prompt = <<<PROMPT
Generate {$count} content ideas for the topic: {$topic}

Output as JSON array of objects with:
- title: compelling title
- description: brief description
- keywords: array of relevant keywords
- content_type: blog/post/page/newsletter

Output only valid JSON array:
PROMPT;

        $response = $this->generate($prompt, ['temperature' => 0.8]);
        
        if (!$response) {
            return null;
        }
        
        return $this->extractJson($response);
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function addContext(string $key, $value): void
    {
        $this->context[$key] = $value;
    }

    public function clearContext(): void
    {
        $this->context = [];
    }

    private function buildSystemPrompt(array $options): string
    {
        $basePrompt = "You are an AI assistant for Curly CMS, a content management system. ";
        $basePrompt .= "Help users create, manage, and optimize content. ";
        $basePrompt .= "Always follow best practices for SEO, accessibility, and user experience.";
        
        if (!empty($this->context)) {
            $basePrompt .= "\n\nContext:\n" . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        if (!empty($options['system_prompt'])) {
            $basePrompt .= "\n\n" . $options['system_prompt'];
        }
        
        return $basePrompt;
    }

    private function buildPrompt(string $prompt, array $options): string
    {
        if (!empty($options['template'])) {
            $templateFile = CMS_ROOT . "/storage/ai/templates/{$options['template']}.txt";
            
            if (file_exists($templateFile)) {
                $template = file_get_contents($templateFile);
                $prompt = str_replace('{prompt}', $prompt, $template);
            }
        }
        
        return $prompt;
    }

    private function extractJson(string $response): ?array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/^```\s*/', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);
        
        $json = json_decode(trim($response), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to find JSON in response
            if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
                $json = json_decode($matches[0], true);
            }
            
            if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
                $json = json_decode($matches[0], true);
            }
        }
        
        return $json ?? null;
    }

    private function logUsage(string $prompt, string $response, int $tokens, float $duration): void
    {
        try {
            $this->db->insert('ai_usage_log', [
                'provider' => $this->config['provider'],
                'model' => $this->config['model'],
                'prompt_hash' => md5($prompt),
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($response),
                'tokens_used' => $tokens,
                'duration_ms' => (int) ($duration * 1000),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            error_log("AI usage log error: " . $e->getMessage());
        }
    }

    private function logError(string $prompt, string $error): void
    {
        try {
            $this->db->insert('ai_error_log', [
                'provider' => $this->config['provider'],
                'model' => $this->config['model'],
                'prompt_hash' => md5($prompt),
                'error_message' => $error,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            error_log("AI error log error: " . $e->getMessage());
        }
    }

    public function getProvider(): AIProvider
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->config['model'];
    }

    public function getUsageStats(int $days = 30): array
    {
        return $this->db->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(tokens_used) as tokens,
                AVG(duration_ms) as avg_duration
             FROM ai_usage_log
             WHERE created_at >= datetime('now', '-{$days} days')
             GROUP BY DATE(created_at)
             ORDER BY date DESC"
        );
    }
}
