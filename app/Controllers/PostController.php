<?php
/**
 * Post Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\Helper;
use CurlyCMS\Core\HttpException;

class PostController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $status = $this->input('status');
        $category = $this->input('category');
        $search = $this->input('search');
        
        $sql = "SELECT p.*, c.name as category_name, u.name as author_name 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if ($category) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int) $category;
        }
        
        if ($search) {
            $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $posts = $this->db->fetchAll($sql, $params);
        
        $categories = $this->db->fetchAll(
            "SELECT id, name FROM categories ORDER BY name ASC"
        );
        
        $this->render('backend.posts.index', [
            'posts' => $posts,
            'categories' => $categories,
            'status' => $status,
            'category' => $category,
            'search' => $search,
            'title' => 'Posts'
        ]);
    }

    public function create(): void
    {
        $categories = $this->db->fetchAll(
            "SELECT id, name FROM categories ORDER BY name ASC"
        );
        
        $this->render('backend.posts.form', [
            'post' => null,
            'categories' => $categories,
            'structure' => $this->getDefaultStructure(),
            'title' => 'Create Post'
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
            $this->redirect('/admin/posts/create');
            return;
        }
        
        // Check slug uniqueness
        $existing = $this->db->fetch(
            "SELECT id FROM posts WHERE slug = ?",
            [$this->input('slug')]
        );
        
        if ($existing) {
            $this->withError('slug', 'A post with this slug already exists.');
            $this->withInput();
            $this->redirect('/admin/posts/create');
            return;
        }
        
        $structure = $this->input('structure');
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }
        
        $status = $this->input('status', 'draft');
        $publishedAt = null;
        
        if ($status === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }
        
        $postId = $this->db->insert('posts', [
            'title' => $this->input('title'),
            'slug' => $this->input('slug'),
            'excerpt' => $this->input('excerpt'),
            'content' => $this->input('content'),
            'structure' => json_encode($structure),
            'category_id' => (int) $this->input('category_id') ?: null,
            'author_id' => $this->session->get('user_id'),
            'status' => $status,
            'featured_image' => $this->input('featured_image'),
            'published_at' => $publishedAt,
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'meta_keywords' => $this->input('meta_keywords'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // AI SEO optimization if requested
        if ($this->input('ai_optimize_seo') && $this->ai) {
            $this->optimizePostSEO($postId);
        }
        
        $this->session->flash('success', 'Post created successfully.');
        $this->redirect('/admin/posts');
    }

    public function edit(int $id): void
    {
        $post = $this->db->fetch(
            "SELECT * FROM posts WHERE id = ?",
            [$id]
        );
        
        if (!$post) {
            throw new HttpException(404, 'Post not found');
        }
        
        $post['structure'] = json_decode($post['structure'] ?? '{}', true);
        
        $categories = $this->db->fetchAll(
            "SELECT id, name FROM categories ORDER BY name ASC"
        );
        
        $this->render('backend.posts.form', [
            'post' => $post,
            'categories' => $categories,
            'structure' => $post['structure'],
            'title' => 'Edit Post'
        ]);
    }

    public function update(int $id): void
    {
        $post = $this->db->fetch(
            "SELECT id, status FROM posts WHERE id = ?",
            [$id]
        );
        
        if (!$post) {
            throw new HttpException(404, 'Post not found');
        }
        
        $validation = $this->validate([
            'title' => 'required|min:2',
            'slug' => 'required|min:2'
        ]);
        
        if (!$validation['valid']) {
            $this->withErrors($validation['errors']);
            $this->redirect('/admin/posts/' . $id . '/edit');
            return;
        }
        
        $existing = $this->db->fetch(
            "SELECT id FROM posts WHERE slug = ? AND id != ?",
            [$this->input('slug'), $id]
        );
        
        if ($existing) {
            $this->withError('slug', 'A post with this slug already exists.');
            $this->redirect('/admin/posts/' . $id . '/edit');
            return;
        }
        
        $structure = $this->input('structure');
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }
        
        $status = $this->input('status', 'draft');
        $publishedAt = $post['status'] === 'published' ? null : ($status === 'published' ? date('Y-m-d H:i:s') : null);
        
        $updateData = [
            'title' => $this->input('title'),
            'slug' => $this->input('slug'),
            'excerpt' => $this->input('excerpt'),
            'content' => $this->input('content'),
            'structure' => json_encode($structure),
            'category_id' => (int) $this->input('category_id') ?: null,
            'status' => $status,
            'featured_image' => $this->input('featured_image'),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'meta_keywords' => $this->input('meta_keywords'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($publishedAt) {
            $updateData['published_at'] = $publishedAt;
        }
        
        $this->db->update('posts', $updateData, ['id' => $id]);
        
        if ($this->input('ai_optimize_seo') && $this->ai) {
            $this->optimizePostSEO($id);
        }
        
        $this->session->flash('success', 'Post updated successfully.');
        $this->redirect('/admin/posts');
    }

    public function destroy(int $id): void
    {
        $post = $this->db->fetch(
            "SELECT id FROM posts WHERE id = ?",
            [$id]
        );
        
        if (!$post) {
            throw new HttpException(404, 'Post not found');
        }
        
        $this->db->delete('posts', ['id' => $id]);
        
        $this->session->flash('success', 'Post deleted successfully.');
        $this->redirect('/admin/posts');
    }

    private function getDefaultStructure(): array
    {
        return [
            'type' => 'container',
            'class' => 'post-content',
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
                                    'content' => 'Post Title'
                                ],
                                [
                                    'type' => 'paragraph',
                                    'content' => 'Start writing your post content here...'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function optimizePostSEO(int $postId): void
    {
        $post = $this->db->fetch(
            "SELECT title, content, meta_keywords FROM posts WHERE id = ?",
            [$postId]
        );
        
        if (!$post) {
            return;
        }
        
        $keywords = array_filter(array_map('trim', explode(',', $post['meta_keywords'] ?? '')));
        
        $metaTitle = $this->ai->generateMetaTitle($post['content'] ?? $post['title'], $keywords);
        $metaDescription = $this->ai->generateMetaDescription($post['content'] ?? '', $keywords);
        
        // Generate excerpt if not set
        $excerpt = $this->ai->generateExcerpt($post['content'] ?? '');
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        if ($metaTitle) {
            $updateData['meta_title'] = $metaTitle;
        }
        
        if ($metaDescription) {
            $updateData['meta_description'] = $metaDescription;
        }
        
        if ($excerpt && empty($post['excerpt'])) {
            $updateData['excerpt'] = $excerpt;
        }
        
        $this->db->update('posts', $updateData, ['id' => $postId]);
    }
}
