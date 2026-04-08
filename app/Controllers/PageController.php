<?php
/**
 * Page Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\Helper;
use CurlyCMS\Core\HttpException;

class PageController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $status = $this->input('status');
        $search = $this->input('search');
        
        $sql = "SELECT p.*, u.name as author_name 
                FROM pages p 
                LEFT JOIN users u ON p.author_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY p.sort_order ASC, p.updated_at DESC";
        
        $pages = $this->db->fetchAll($sql, $params);
        
        // Build page tree
        $pagesTree = $this->buildPageTree($pages);
        
        $this->render('backend.pages.index', [
            'pages' => $pages,
            'pagesTree' => $pagesTree,
            'status' => $status,
            'search' => $search,
            'title' => 'Pages'
        ]);
    }

    private function buildPageTree(array $pages, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($pages as $page) {
            if ((int) $page['parent_id'] === $parentId) {
                $children = $this->buildPageTree($pages, (int) $page['id']);
                if ($children) {
                    $page['children'] = $children;
                }
                $tree[] = $page;
            }
        }
        
        return $tree;
    }

    public function create(): void
    {
        $parentPages = $this->db->fetchAll(
            "SELECT id, title, parent_id FROM pages ORDER BY title ASC"
        );
        
        $templates = $this->getTemplates();
        
        $this->render('backend.pages.form', [
            'page' => null,
            'parentPages' => $this->buildPageTree($parentPages),
            'templates' => $templates,
            'structure' => $this->getDefaultStructure(),
            'title' => 'Create Page'
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'title' => 'required|min:2',
            'slug' => 'required|min:2'
        ]);
        
        if (!$validation['valid']) {
            $this->withErrors($validation['errors']);
            $this->withInput();
            $this->redirect('/admin/pages/create');
            return;
        }
        
        // Check slug uniqueness
        $existing = $this->db->fetch(
            "SELECT id FROM pages WHERE slug = ?",
            [$this->input('slug')]
        );
        
        if ($existing) {
            $this->withError('slug', 'A page with this slug already exists.');
            $this->withInput();
            $this->redirect('/admin/pages/create');
            return;
        }
        
        $structure = $this->input('structure');
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }
        
        $pageId = $this->db->insert('pages', [
            'title' => $this->input('title'),
            'slug' => $this->input('slug'),
            'content' => $this->input('content'),
            'structure' => json_encode($structure),
            'parent_id' => (int) $this->input('parent_id') ?: null,
            'author_id' => $this->session->get('user_id'),
            'status' => $this->input('status', 'draft'),
            'template' => $this->input('template'),
            'sort_order' => (int) $this->input('sort_order', 0),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'meta_keywords' => $this->input('meta_keywords'),
            'og_image' => $this->input('og_image'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // AI SEO optimization if requested
        if ($this->input('ai_optimize_seo') && $this->ai) {
            $this->optimizePageSEO($pageId);
        }
        
        $this->session->flash('success', 'Page created successfully.');
        $this->redirect('/admin/pages');
    }

    public function edit(int $id): void
    {
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE id = ?",
            [$id]
        );
        
        if (!$page) {
            throw new HttpException(404, 'Page not found');
        }
        
        $page['structure'] = json_decode($page['structure'] ?? '{}', true);
        
        $parentPages = $this->db->fetchAll(
            "SELECT id, title, parent_id FROM pages WHERE id != ? ORDER BY title ASC",
            [$id]
        );
        
        $templates = $this->getTemplates();
        
        $this->render('backend.pages.form', [
            'page' => $page,
            'parentPages' => $this->buildPageTree($parentPages),
            'templates' => $templates,
            'structure' => $page['structure'],
            'title' => 'Edit Page'
        ]);
    }

    public function update(int $id): void
    {
        $page = $this->db->fetch(
            "SELECT id FROM pages WHERE id = ?",
            [$id]
        );
        
        if (!$page) {
            throw new HttpException(404, 'Page not found');
        }
        
        $validation = $this->validate([
            'title' => 'required|min:2',
            'slug' => 'required|min:2'
        ]);
        
        if (!$validation['valid']) {
            $this->withErrors($validation['errors']);
            $this->redirect('/admin/pages/' . $id . '/edit');
            return;
        }
        
        // Check slug uniqueness
        $existing = $this->db->fetch(
            "SELECT id FROM pages WHERE slug = ? AND id != ?",
            [$this->input('slug'), $id]
        );
        
        if ($existing) {
            $this->withError('slug', 'A page with this slug already exists.');
            $this->redirect('/admin/pages/' . $id . '/edit');
            return;
        }
        
        $structure = $this->input('structure');
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }
        
        $this->db->update('pages', [
            'title' => $this->input('title'),
            'slug' => $this->input('slug'),
            'content' => $this->input('content'),
            'structure' => json_encode($structure),
            'parent_id' => (int) $this->input('parent_id') ?: null,
            'status' => $this->input('status', 'draft'),
            'template' => $this->input('template'),
            'sort_order' => (int) $this->input('sort_order', 0),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'meta_keywords' => $this->input('meta_keywords'),
            'og_image' => $this->input('og_image'),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
        
        // AI SEO optimization if requested
        if ($this->input('ai_optimize_seo') && $this->ai) {
            $this->optimizePageSEO($id);
        }
        
        $this->session->flash('success', 'Page updated successfully.');
        $this->redirect('/admin/pages');
    }

    public function destroy(int $id): void
    {
        $page = $this->db->fetch(
            "SELECT id FROM pages WHERE id = ?",
            [$id]
        );
        
        if (!$page) {
            throw new HttpException(404, 'Page not found');
        }
        
        // Check for child pages
        $children = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM pages WHERE parent_id = ?",
            [$id]
        );
        
        if ($children > 0) {
            $this->session->flash('error', 'Cannot delete page with child pages.');
            $this->redirect('/admin/pages');
            return;
        }
        
        $this->db->delete('pages', ['id' => $id]);
        
        $this->session->flash('success', 'Page deleted successfully.');
        $this->redirect('/admin/pages');
    }

    public function duplicate(int $id): void
    {
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE id = ?",
            [$id]
        );
        
        if (!$page) {
            throw new HttpException(404, 'Page not found');
        }
        
        $newSlug = $page['slug'] . '-copy';
        $counter = 1;
        
        while ($this->db->fetch("SELECT id FROM pages WHERE slug = ?", [$newSlug])) {
            $counter++;
            $newSlug = $page['slug'] . '-copy-' . $counter;
        }
        
        $this->db->insert('pages', [
            'title' => $page['title'] . ' (Copy)',
            'slug' => $newSlug,
            'content' => $page['content'],
            'structure' => $page['structure'],
            'parent_id' => $page['parent_id'],
            'author_id' => $this->session->get('user_id'),
            'status' => 'draft',
            'template' => $page['template'],
            'sort_order' => $page['sort_order'],
            'meta_title' => $page['meta_title'],
            'meta_description' => $page['meta_description'],
            'meta_keywords' => $page['meta_keywords'],
            'og_image' => $page['og_image'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->session->flash('success', 'Page duplicated successfully.');
        $this->redirect('/admin/pages');
    }

    private function getTemplates(): array
    {
        $templates = [];
        $templateDir = CMS_ROOT . '/themes/default/templates';
        
        if (is_dir($templateDir)) {
            $files = glob($templateDir . '/*.php');
            foreach ($files as $file) {
                $name = basename($file, '.php');
                $templates[$name] = ucfirst(str_replace('-', ' ', $name));
            }
        }
        
        return $templates;
    }

    private function getDefaultStructure(): array
    {
        return [
            'type' => 'container',
            'class' => '',
            'children' => [
                [
                    'type' => 'row',
                    'children' => [
                        [
                            'type' => 'column',
                            'cols' => 12,
                            'children' => [
                                [
                                    'type' => 'heading',
                                    'level' => 1,
                                    'content' => 'Page Title'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function optimizePageSEO(int $pageId): void
    {
        $page = $this->db->fetch(
            "SELECT title, content, meta_keywords FROM pages WHERE id = ?",
            [$pageId]
        );
        
        if (!$page) {
            return;
        }
        
        $keywords = array_filter(array_map('trim', explode(',', $page['meta_keywords'] ?? '')));
        
        // Generate meta title if not set
        $metaTitle = $this->ai->generateMetaTitle($page['content'] ?? $page['title'], $keywords);
        
        // Generate meta description if not set
        $metaDescription = $this->ai->generateMetaDescription($page['content'] ?? '', $keywords);
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        if ($metaTitle) {
            $updateData['meta_title'] = $metaTitle;
        }
        
        if ($metaDescription) {
            $updateData['meta_description'] = $metaDescription;
        }
        
        $this->db->update('pages', $updateData, ['id' => $pageId]);
    }
}
