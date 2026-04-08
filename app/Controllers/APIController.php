<?php
/**
 * API Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class APIController extends BaseController
{
    public function aiChat(): void
    {
        $this->validateAPIKey();
        
        if (!$this->ai) {
            $this->json(['error' => 'AI service not configured'], 400);
            return;
        }
        
        $message = $this->input('message');
        $history = $this->input('history', []);
        $options = $this->input('options', []);
        
        if (empty($message)) {
            $this->json(['error' => 'Message is required'], 400);
            return;
        }
        
        $response = $this->ai->generate($message, array_merge($options, [
            'history' => $history
        ]));
        
        if ($response === null) {
            $this->json(['error' => 'AI generation failed'], 500);
            return;
        }
        
        $this->json([
            'success' => true,
            'response' => $response
        ]);
    }

    public function aiComplete(): void
    {
        $this->validateAPIKey();
        
        if (!$this->ai) {
            $this->json(['error' => 'AI service not configured'], 400);
            return;
        }
        
        $prompt = $this->input('prompt');
        
        if (empty($prompt)) {
            $this->json(['error' => 'Prompt is required'], 400);
            return;
        }
        
        $response = $this->ai->generate($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 100
        ]);
        
        $this->json([
            'success' => true,
            'completion' => $response
        ]);
    }

    public function aiEmbed(): void
    {
        $this->validateAPIKey();
        
        $content = $this->input('content');
        
        if (empty($content)) {
            $this->json(['error' => 'Content is required'], 400);
            return;
        }
        
        // Placeholder for embedding generation
        // In production, implement actual embedding API
        $this->json([
            'success' => true,
            'embedding' => [],
            'dimensions' => 0
        ]);
    }

    public function pages(): void
    {
        $this->validateAPIKey();
        
        $pages = $this->db->fetchAll(
            "SELECT id, title, slug, status, created_at, updated_at 
             FROM pages 
             ORDER BY title ASC"
        );
        
        $this->json([
            'success' => true,
            'pages' => $pages
        ]);
    }

    public function posts(): void
    {
        $this->validateAPIKey();
        
        $limit = (int) $this->input('limit', 10);
        $offset = (int) $this->input('offset', 0);
        
        $posts = $this->db->fetchAll(
            "SELECT id, title, slug, excerpt, status, created_at, published_at 
             FROM posts 
             WHERE status = 'published'
             ORDER BY published_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
        
        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE status = 'published'"
        );
        
        $this->json([
            'success' => true,
            'posts' => $posts,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    public function media(): void
    {
        $this->validateAPIKey();
        
        $limit = (int) $this->input('limit', 50);
        
        $media = $this->db->fetchAll(
            "SELECT id, filename, path, type, mime_type, size, width, height, alt_text 
             FROM media 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$limit]
        );
        
        $this->json([
            'success' => true,
            'media' => $media
        ]);
    }

    private function validateAPIKey(): void
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $this->input('api_key');
        
        if (empty($apiKey)) {
            http_response_code(401);
            $this->json(['error' => 'API key required'], 401);
            exit;
        }
        
        // Check against stored API keys
        $validKey = $this->db->fetch(
            "SELECT id FROM api_keys WHERE `key` = ? AND active = 1",
            [$apiKey]
        );
        
        if (!$validKey) {
            http_response_code(401);
            $this->json(['error' => 'Invalid API key'], 401);
            exit;
        }
    }
}
