<?php
/**
 * Agent-to-Agent (A2A) Protocol Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class A2AController extends BaseController
{
    public function message(): void
    {
        $this->validateA2ARequest();
        
        $fromAgent = $this->input('from_agent');
        $toAgent = $this->input('to_agent');
        $message = $this->input('message');
        $messageType = $this->input('message_type', 'request');
        
        // Log the message
        $this->db->insert('a2a_messages', [
            'from_agent' => $fromAgent,
            'to_agent' => $toAgent,
            'message_type' => $messageType,
            'content' => json_encode($message),
            'status' => 'received',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Process message based on type
        $response = $this->processMessage($fromAgent, $toAgent, $message, $messageType);
        
        $this->json([
            'success' => true,
            'message_id' => $this->db->getLastInsertId(),
            'response' => $response
        ]);
    }

    public function task(): void
    {
        $this->validateA2ARequest();
        
        $taskId = $this->input('task_id');
        $agentId = $this->input('agent_id');
        $taskType = $this->input('task_type');
        $params = $this->input('params', []);
        
        // Create or update task
        if ($taskId) {
            $task = $this->db->fetch(
                "SELECT * FROM a2a_tasks WHERE id = ?",
                [$taskId]
            );
            
            if (!$task) {
                $this->json(['error' => 'Task not found'], 404);
                return;
            }
            
            // Update task
            $this->db->update('a2a_tasks', [
                'status' => $params['status'] ?? $task['status'],
                'result' => json_encode($params['result'] ?? []),
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $taskId]);
            
        } else {
            // Create new task
            $taskId = $this->db->insert('a2a_tasks', [
                'agent_id' => $agentId,
                'task_type' => $taskType,
                'params' => json_encode($params),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Execute task if needed
        if ($taskType && !isset($params['status'])) {
            $result = $this->executeTask($taskType, $params);
            
            $this->db->update('a2a_tasks', [
                'status' => 'completed',
                'result' => json_encode($result),
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $taskId]);
        }
        
        $this->json([
            'success' => true,
            'task_id' => $taskId
        ]);
    }

    public function status(): void
    {
        $this->validateA2ARequest();
        
        $taskId = $this->input('task_id');
        
        if ($taskId) {
            $task = $this->db->fetch(
                "SELECT * FROM a2a_tasks WHERE id = ?",
                [$taskId]
            );
            
            if (!$task) {
                $this->json(['error' => 'Task not found'], 404);
                return;
            }
            
            $task['params'] = json_decode($task['params'], true);
            $task['result'] = json_decode($task['result'], true);
            
            $this->json([
                'success' => true,
                'task' => $task
            ]);
            
        } else {
            // Return agent status
            $agentId = $this->input('agent_id', 'cms-agent');
            
            $stats = [
                'pending_tasks' => (int) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM a2a_tasks WHERE status = 'pending'"
                ),
                'completed_tasks' => (int) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM a2a_tasks WHERE status = 'completed'"
                ),
                'messages_received' => (int) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM a2a_messages WHERE to_agent = ?",
                    [$agentId]
                )
            ];
            
            $this->json([
                'success' => true,
                'agent_id' => $agentId,
                'status' => 'active',
                'stats' => $stats
            ]);
        }
    }

    private function validateA2ARequest(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            http_response_code(401);
            $this->json(['error' => 'Authorization required'], 401);
            exit;
        }
        
        $token = $matches[1];
        
        $validToken = $this->db->fetch(
            "SELECT id, agent_id FROM a2a_tokens WHERE token = ? AND active = 1",
            [$token]
        );
        
        if (!$validToken) {
            http_response_code(401);
            $this->json(['error' => 'Invalid token'], 401);
            exit;
        }
    }

    private function processMessage(string $fromAgent, string $toAgent, array $message, string $type): array
    {
        $action = $message['action'] ?? '';
        
        switch ($action) {
            case 'ping':
                return ['action' => 'pong', 'timestamp' => time()];
                
            case 'get_content':
                return $this->getContentForAgent($message);
                
            case 'update_content':
                return $this->updateContentFromAgent($message);
                
            case 'query':
                return $this->queryContent($message);
                
            default:
                return ['error' => 'Unknown action', 'action' => $action];
        }
    }

    private function getContentForAgent(array $message): array
    {
        $type = $message['content_type'] ?? 'page';
        $id = $message['content_id'] ?? null;
        
        if ($type === 'page') {
            if ($id) {
                $content = $this->db->fetch(
                    "SELECT * FROM pages WHERE id = ?",
                    [$id]
                );
            } else {
                $content = $this->db->fetchAll(
                    "SELECT * FROM pages WHERE status = 'published' ORDER BY updated_at DESC LIMIT 10"
                );
            }
        } elseif ($type === 'post') {
            if ($id) {
                $content = $this->db->fetch(
                    "SELECT * FROM posts WHERE id = ?",
                    [$id]
                );
            } else {
                $content = $this->db->fetchAll(
                    "SELECT * FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 10"
                );
            }
        } else {
            $content = [];
        }
        
        return ['content' => $content];
    }

    private function updateContentFromAgent(array $message): array
    {
        $type = $message['content_type'] ?? 'page';
        $id = $message['content_id'] ?? null;
        $data = $message['data'] ?? [];
        
        if (!$id) {
            return ['error' => 'content_id required'];
        }
        
        if ($type === 'page') {
            $table = 'pages';
        } elseif ($type === 'post') {
            $table = 'posts';
        } else {
            return ['error' => 'Invalid content_type'];
        }
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        foreach (['title', 'content', 'structure'] as $field) {
            if (isset($data[$field])) {
                if ($field === 'structure') {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }
        
        $this->db->update($table, $updateData, ['id' => $id]);
        
        return ['success' => true, 'updated_id' => $id];
    }

    private function queryContent(array $message): array
    {
        $query = $message['query'] ?? '';
        $type = $message['content_type'] ?? 'all';
        
        $results = [];
        
        if (strlen($query) >= 2) {
            $searchTerm = '%' . $query . '%';
            
            if ($type === 'all' || $type === 'page') {
                $results['pages'] = $this->db->fetchAll(
                    "SELECT id, title, slug FROM pages WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)",
                    [$searchTerm, $searchTerm]
                );
            }
            
            if ($type === 'all' || $type === 'post') {
                $results['posts'] = $this->db->fetchAll(
                    "SELECT id, title, slug FROM posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)",
                    [$searchTerm, $searchTerm]
                );
            }
        }
        
        return ['results' => $results];
    }

    private function executeTask(string $taskType, array $params): array
    {
        switch ($taskType) {
            case 'generate_page':
                return $this->taskGeneratePage($params);
                
            case 'generate_post':
                return $this->taskGeneratePost($params);
                
            case 'optimize_seo':
                return $this->taskOptimizeSEO($params);
                
            case 'batch_update':
                return $this->taskBatchUpdate($params);
                
            default:
                return ['error' => 'Unknown task type'];
        }
    }

    private function taskGeneratePage(array $params): array
    {
        $title = $params['title'] ?? 'Generated Page';
        $prompt = $params['prompt'] ?? '';
        
        $structure = null;
        if ($this->ai && $prompt) {
            $structure = $this->ai->generateStructure('page', $prompt);
        }
        
        $slug = \CurlyCMS\Core\Helper::slug($title);
        $counter = 1;
        while ($this->db->fetch("SELECT id FROM pages WHERE slug = ?", [$slug])) {
            $slug = \CurlyCMS\Core\Helper::slug($title) . '-' . $counter++;
        }
        
        $id = $this->db->insert('pages', [
            'title' => $title,
            'slug' => $slug,
            'content' => $params['content'] ?? '',
            'structure' => json_encode($structure ?? []),
            'status' => $params['status'] ?? 'draft',
            'author_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['page_id' => $id, 'slug' => $slug];
    }

    private function taskGeneratePost(array $params): array
    {
        $title = $params['title'] ?? 'Generated Post';
        $prompt = $params['prompt'] ?? '';
        
        $content = null;
        if ($this->ai && $prompt) {
            $content = $this->ai->generate($prompt);
        }
        
        $slug = \CurlyCMS\Core\Helper::slug($title);
        $counter = 1;
        while ($this->db->fetch("SELECT id FROM posts WHERE slug = ?", [$slug])) {
            $slug = \CurlyCMS\Core\Helper::slug($title) . '-' . $counter++;
        }
        
        $id = $this->db->insert('posts', [
            'title' => $title,
            'slug' => $slug,
            'content' => $content ?? $params['content'] ?? '',
            'excerpt' => $params['excerpt'] ?? '',
            'status' => $params['status'] ?? 'draft',
            'author_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['post_id' => $id, 'slug' => $slug];
    }

    private function taskOptimizeSEO(array $params): array
    {
        if (!$this->ai) {
            return ['error' => 'AI service not configured'];
        }
        
        $contentId = $params['content_id'] ?? null;
        $contentType = $params['content_type'] ?? 'page';
        
        if (!$contentId) {
            return ['error' => 'content_id required'];
        }
        
        $table = $contentType === 'post' ? 'posts' : 'pages';
        $content = $this->db->fetch(
            "SELECT * FROM {$table} WHERE id = ?",
            [$contentId]
        );
        
        if (!$content) {
            return ['error' => 'Content not found'];
        }
        
        $keywords = array_filter(array_map('trim', explode(',', $content['meta_keywords'] ?? '')));
        
        $optimized = $this->ai->optimizeSEO($content['content'] ?? '', $keywords);
        $metaTitle = $this->ai->generateMetaTitle($content['content'] ?? $content['title'], $keywords);
        $metaDescription = $this->ai->generateMetaDescription($content['content'] ?? '', $keywords);
        
        $this->db->update($table, [
            'content' => $optimized ?? $content['content'],
            'meta_title' => $metaTitle ?? $content['meta_title'],
            'meta_description' => $metaDescription ?? $content['meta_description'],
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $contentId]);
        
        return [
            'optimized' => true,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription
        ];
    }

    private function taskBatchUpdate(array $params): array
    {
        $updates = $params['updates'] ?? [];
        $results = [];
        
        foreach ($updates as $update) {
            $type = $update['type'] ?? 'page';
            $id = $update['id'] ?? null;
            $data = $update['data'] ?? [];
            
            if (!$id) {
                $results[] = ['error' => 'ID required'];
                continue;
            }
            
            $table = $type === 'post' ? 'posts' : 'pages';
            
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            foreach ($data as $key => $value) {
                $updateData[$key] = $value;
            }
            
            $this->db->update($table, $updateData, ['id' => $id]);
            $results[] = ['success' => true, 'id' => $id];
        }
        
        return ['results' => $results];
    }
}
