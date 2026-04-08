<?php
/**
 * AI Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class AIController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $usageStats = [];
        $errorLogs = [];
        
        if ($this->ai) {
            try {
                $usageStats = $this->ai->getUsageStats(30);
                
                $errorLogs = $this->db->fetchAll(
                    "SELECT * FROM ai_error_log 
                     ORDER BY created_at DESC 
                     LIMIT 20"
                );
            } catch (\Throwable $e) {
                // Tables might not exist yet
            }
        }
        
        $this->render('backend.ai.index', [
            'ai' => $this->ai,
            'usageStats' => $usageStats,
            'errorLogs' => $errorLogs,
            'title' => 'AI Assistant'
        ]);
    }

    public function generate(): void
    {
        if (!$this->ai) {
            $this->json(['error' => 'AI service not configured'], 400);
            return;
        }
        
        $prompt = $this->input('prompt');
        $options = $this->input('options', []);
        
        if (empty($prompt)) {
            $this->json(['error' => 'Prompt is required'], 400);
            return;
        }
        
        $result = $this->ai->generate($prompt, $options);
        
        if ($result === null) {
            $this->json(['error' => 'AI generation failed'], 500);
            return;
        }
        
        $this->json([
            'success' => true,
            'content' => $result
        ]);
    }

    public function generateStructure(): void
    {
        if (!$this->ai) {
            $this->json(['error' => 'AI service not configured'], 400);
            return;
        }
        
        $type = $this->input('type', 'page');
        $description = $this->input('description');
        
        if (empty($description)) {
            $this->json(['error' => 'Description is required'], 400);
            return;
        }
        
        $structure = $this->ai->generateStructure($type, $description);
        
        if ($structure === null) {
            $this->json(['error' => 'Structure generation failed'], 500);
            return;
        }
        
        $this->json([
            'success' => true,
            'structure' => $structure
        ]);
    }

    public function optimizeSEO(): void
    {
        if (!$this->ai) {
            $this->json(['error' => 'AI service not configured'], 400);
            return;
        }
        
        $content = $this->input('content');
        $keywords = $this->input('keywords', []);
        
        if (empty($content)) {
            $this->json(['error' => 'Content is required'], 400);
            return;
        }
        
        $optimized = $this->ai->optimizeSEO($content, $keywords);
        $metaTitle = $this->ai->generateMetaTitle($content, $keywords);
        $metaDescription = $this->ai->generateMetaDescription($content, $keywords);
        
        $this->json([
            'success' => true,
            'optimized_content' => $optimized,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription
        ]);
    }

    public function translate(): void
    {
        if (!$this->ai) {
            $this->json(['error' => 'AI service not configured'], 400);
            return;
        }
        
        $content = $this->input('content');
        $targetLanguage = $this->input('target_language', 'English');
        
        if (empty($content)) {
            $this->json(['error' => 'Content is required'], 400);
            return;
        }
        
        $translated = $this->ai->translateContent($content, $targetLanguage);
        
        if ($translated === null) {
            $this->json(['error' => 'Translation failed'], 500);
            return;
        }
        
        $this->json([
            'success' => true,
            'translated_content' => $translated
        ]);
    }
}
