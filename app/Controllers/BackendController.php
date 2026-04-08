<?php
/**
 * Backend Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class BackendController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function dashboard(): void
    {
        $stats = [
            'pages' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM pages"),
            'posts' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'published'"),
            'drafts' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'draft'"),
            'media' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM media"),
            'users' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'page_views' => (int) $this->db->fetchColumn("SELECT COALESCE(SUM(views), 0) FROM pages"),
            'post_views' => (int) $this->db->fetchColumn("SELECT COALESCE(SUM(views), 0) FROM posts")
        ];
        
        $recentPosts = $this->db->fetchAll(
            "SELECT id, title, slug, status, created_at 
             FROM posts 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
        
        $recentPages = $this->db->fetchAll(
            "SELECT id, title, slug, status, updated_at 
             FROM pages 
             ORDER BY updated_at DESC 
             LIMIT 5"
        );
        
        // AI usage stats if available
        $aiStats = null;
        if ($this->ai) {
            try {
                $aiStats = $this->db->fetch(
                    "SELECT 
                        COUNT(*) as total_requests,
                        SUM(tokens_used) as total_tokens,
                        AVG(duration_ms) as avg_duration
                     FROM ai_usage_log 
                     WHERE created_at >= datetime('now', '-7 days')"
                );
            } catch (\Throwable $e) {
                // AI usage log table might not exist yet
            }
        }
        
        $this->render('backend.dashboard', [
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'recentPages' => $recentPages,
            'aiStats' => $aiStats,
            'title' => 'Dashboard'
        ]);
    }
}
