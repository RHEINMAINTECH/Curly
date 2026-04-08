<?php
/**
 * Model Context Server (MCS) Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class MCSController extends BaseController
{
    public function context(): void
    {
        $this->validateMCSRequest();
        
        $modelId = $this->input('model_id');
        $query = $this->input('query');
        
        // Gather relevant context for the AI model
        $context = [
            'site' => $this->getSiteContext(),
            'content' => $this->getContentContext($query),
            'structure' => $this->getStructureContext()
        ];
        
        $this->json([
            'success' => true,
            'context' => $context
        ]);
    }

    public function execute(): void
    {
        $this->validateMCSRequest();
        
        $action = $this->input('action');
        $params = $this->input('params', []);
        
        $result = $this->executeAction($action, $params);
        
        $this->json([
            'success' => true,
            'result' => $result
        ]);
    }

    private function validateMCSRequest(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            http_response_code(401);
            $this->json(['error' => 'Authorization required'], 401);
            exit;
        }
        
        $token = $matches[1];
        
        // Validate MCP token
        $validToken = $this->db->fetch(
            "SELECT id FROM mcs_tokens WHERE token = ? AND active = 1",
            [$token]
        );
        
        if (!$validToken) {
            http_response_code(401);
            $this->json(['error' => 'Invalid token'], 401);
            exit;
        }
    }

    private function getSiteContext(): array
    {
        $settings = [];
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        return [
            'name' => $settings['site_title'] ?? 'Curly CMS',
            'description' => $settings['site_description'] ?? '',
            'url' => $settings['site_url'] ?? '',
            'language' => $settings['site_language'] ?? 'en'
        ];
    }

    private function getContentContext(string $query): array
    {
        $content = [];
        
        // Get relevant pages
        if (strlen($query) >= 2) {
            $searchTerm = '%' . $query . '%';
            
            $pages = $this->db->fetchAll(
                "SELECT id, title, slug, content, meta_description 
                 FROM pages 
                 WHERE status = 'published' 
                 AND (title LIKE ? OR content LIKE ?)
                 LIMIT 5",
                [$searchTerm, $searchTerm]
            );
            
            $posts = $this->db->fetchAll(
                "SELECT id, title, slug, excerpt, content 
                 FROM posts 
                 WHERE status = 'published' 
                 AND (title LIKE ? OR content LIKE ?)
                 LIMIT 5",
                [$searchTerm, $searchTerm]
            );
            
            $content['pages'] = $pages;
            $content['posts'] = $posts;
        } else {
            // Return recent content
            $content['recent_pages'] = $this->db->fetchAll(
                "SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY updated_at DESC LIMIT 5"
            );
            $content['recent_posts'] = $this->db->fetchAll(
                "SELECT id, title, slug FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 5"
            );
        }
        
        return $content;
    }

    private function getStructureContext(): array
    {
        return [
            'components' => [
                'container', 'row', 'column', 'heading', 'paragraph',
                'image', 'link', 'button', 'card', 'list', 'divider',
                'video', 'gallery', 'alert', 'badge', 'quote', 'code',
                'accordion', 'tabs', 'table', 'form', 'section'
            ],
            'layouts' => [
                'single_column', 'two_column', 'three_column', 'sidebar_left',
                'sidebar_right', 'full_width', 'hero_section'
            ]
        ];
    }

    private function executeAction(string $action, array $params): array
    {
        switch ($action) {
            case 'create_page':
                return $this->createPage($params);
                
            case 'update_page':
                return $this->updatePage($params);
                
            case 'create_post':
                return $this->createPost($params);
                
            case 'update_post':
                return $this->updatePost($params);
                
            case 'generate_content':
                return $this->generateContent($params);
                
            case 'optimize_seo':
                return $this->optimizeSEO($params);
                
            default:
                return ['error' => 'Unknown action: ' . $action];
        }
    }

    private function createPage(array $params): array
    {
        $title = $params['title'] ?? 'Untitled';
        $slug = $params['slug'] ?? \CurlyCMS\Core\Helper::slug($title);
        $structure = $params['structure'] ?? [];
        
        // Ensure unique slug
        $counter = 1;
        $originalSlug = $slug;
        while ($this->db->fetch("SELECT id FROM pages WHERE slug = ?", [$slug])) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        $id = $this->db->insert('pages', [
            'title' => $title,
            'slug' => $slug,
            'content' => $params['content'] ?? '',
            'structure' => json_encode($structure),
            'status' => $params['status'] ?? 'draft',
            'author_id' => 1, // System user
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['id' => $id, 'slug' => $slug];
    }

    private function updatePage(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        
        if (!$id) {
            return ['error' => 'Page ID required'];
        }
        
        $page = $this->db->fetch("SELECT id FROM pages WHERE id = ?", [$id]);
        
        if (!$page) {
            return ['error' => 'Page not found'];
        }
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        if (isset($params['title'])) {
            $updateData['title'] = $params['title'];
        }
        if (isset($params['content'])) {
            $updateData['content'] = $params['content'];
        }
        if (isset($params['structure'])) {
            $updateData['structure'] = json_encode($params['structure']);
        }
        if (isset($params['status'])) {
            $updateData['status'] = $params['status'];
        }
        
        $this->db->update('pages', $updateData, ['id' => $id]);
        
        return ['success' => true];
    }

    private function createPost(array $params): array
    {
        $title = $params['title'] ?? 'Untitled';
        $slug = $params['slug'] ?? \CurlyCMS\Core\Helper::slug($title);
        
        $counter = 1;
        $originalSlug = $slug;
        while ($this->db->fetch("SELECT id FROM posts WHERE slug = ?", [$slug])) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        $status = $params['status'] ?? 'draft';
        
        $id = $this->db->insert('posts', [
            'title' => $title,
            'slug' => $slug,
            'content' => $params['content'] ?? '',
            'excerpt' => $params['excerpt'] ?? '',
            'structure' => json_encode($params['structure'] ?? []),
            'status' => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'author_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['id' => $id, 'slug' => $slug];
    }

    private function updatePost(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        
        if (!$id) {
            return ['error' => 'Post ID required'];
        }
        
        $post = $this->db->fetch("SELECT id, status FROM posts WHERE id = ?", [$id]);
        
        if (!$post) {
            return ['error' => 'Post not found'];
        }
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        foreach (['title', 'content', 'excerpt', 'structure', 'status'] as $field) {
            if (isset($params[$field])) {
                if ($field === 'structure') {
                    $updateData[$field] = json_encode($params[$field]);
                } else {
                    $updateData[$field] = $params[$field];
                }
            }
        }
        
        // Handle publishing
        if (isset($params['status']) && $params['status'] === 'published' && $post['status'] !== 'published') {
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->update('posts', $updateData, ['id' => $id]);
        
        return ['success' => true];
    }

    private function generateContent(array $params): array
    {
        if (!$this->ai) {
            return ['error' => 'AI service not configured'];
        }
        
        $type = $params['type'] ?? 'text';
        $prompt = $params['prompt'] ?? '';
        
        $content = $this->ai->generate($prompt);
        
        return ['content' => $content];
    }

    private function optimizeSEO(array $params): array
    {
        if (!$this->ai) {
            return ['error' => 'AI service not configured'];
        }
        
        $content = $params['content'] ?? '';
        $keywords = $params['keywords'] ?? [];
        
        return [
            'optimized_content' => $this->ai->optimizeSEO($content, $keywords),
            'meta_title' => $this->ai->generateMetaTitle($content, $keywords),
            'meta_description' => $this->ai->generateMetaDescription($content, $keywords)
        ];
    }
}
