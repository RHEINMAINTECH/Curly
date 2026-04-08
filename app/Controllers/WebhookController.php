<?php
/**
 * Webhook Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class WebhookController extends BaseController
{
    public function ai(): void
    {
        $this->validateWebhookSignature();
        
        $event = $this->input('event');
        $data = $this->input('data', []);
        
        // Log webhook
        $this->db->insert('webhook_logs', [
            'source' => 'ai',
            'event' => $event,
            'payload' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Process event
        switch ($event) {
            case 'content.generated':
                $this->handleContentGenerated($data);
                break;
                
            case 'content.optimized':
                $this->handleContentOptimized($data);
                break;
                
            default:
                // Unknown event - log but acknowledge
                break;
        }
        
        $this->json(['success' => true]);
    }

    public function extension(string $name): void
    {
        $extension = $this->db->fetch(
            "SELECT * FROM extensions WHERE name = ? AND active = 1",
            [$name]
        );
        
        if (!$extension) {
            $this->json(['error' => 'Extension not found or inactive'], 404);
            return;
        }
        
        // Verify webhook signature for extension
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        $expectedSignature = hash_hmac(
            'sha256',
            file_get_contents('php://input'),
            $extension['webhook_secret'] ?? ''
        );
        
        if (!hash_equals($expectedSignature, $signature)) {
            $this->json(['error' => 'Invalid signature'], 401);
            return;
        }
        
        // Log webhook
        $this->db->insert('webhook_logs', [
            'source' => 'extension',
            'event' => $name,
            'payload' => file_get_contents('php://input'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Trigger extension webhook handler
        $extensionDir = CMS_ROOT . '/extensions/' . $name;
        $webhookFile = $extensionDir . '/webhook.php';
        
        if (file_exists($webhookFile)) {
            $sandbox = new \CurlyCMS\Core\Sandbox($extensionDir);
            $sandbox->executeFile($webhookFile, [
                'payload' => $this->input(),
                'db' => $this->db
            ]);
        }
        
        $this->json(['success' => true]);
    }

    private function validateWebhookSignature(): void
    {
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        
        if (empty($signature)) {
            http_response_code(401);
            $this->json(['error' => 'Missing signature'], 401);
            exit;
        }
        
        $secret = getenv('WEBHOOK_SECRET') ?: 'webhook-secret';
        $expectedSignature = hash_hmac(
            'sha256',
            file_get_contents('php://input'),
            $secret
        );
        
        if (!hash_equals($expectedSignature, $signature)) {
            http_response_code(401);
            $this->json(['error' => 'Invalid signature'], 401);
            exit;
        }
    }

    private function handleContentGenerated(array $data): void
    {
        $contentType = $data['content_type'] ?? 'page';
        $contentId = $data['content_id'] ?? null;
        
        if (!$contentId) {
            return;
        }
        
        // Update content with AI-generated data
        if ($contentType === 'page') {
            $this->db->update('pages', [
                'structure' => json_encode($data['structure'] ?? []),
                'content' => $data['content'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $contentId]);
        } elseif ($contentType === 'post') {
            $this->db->update('posts', [
                'structure' => json_encode($data['structure'] ?? []),
                'content' => $data['content'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $contentId]);
        }
    }

    private function handleContentOptimized(array $data): void
    {
        $contentType = $data['content_type'] ?? 'page';
        $contentId = $data['content_id'] ?? null;
        
        if (!$contentId) {
            return;
        }
        
        $table = $contentType === 'post' ? 'posts' : 'pages';
        
        $this->db->update($table, [
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'meta_keywords' => $data['meta_keywords'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $contentId]);
    }
}
