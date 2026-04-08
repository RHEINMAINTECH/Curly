<?php
/**
 * Frontend Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\Helper;
use CurlyCMS\Core\HttpException;

class FrontendController extends BaseController
{
    private array $settings = [];

    protected function init(): void
    {
        $this->loadSettings();
        $this->view->share('settings', $this->settings);
    }

    private function loadSettings(): void
    {
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        
        foreach ($rows as $row) {
            $this->settings[$row['key']] = $row['value'];
        }
    }

    public function home(): void
    {
        $homePage = $this->db->fetch(
            "SELECT * FROM pages WHERE slug = 'home' AND status = 'published'"
        );
        
        if ($homePage) {
            $this->renderPage($homePage);
            return;
        }
        
        $posts = $this->db->fetchAll(
            "SELECT * FROM posts WHERE status = 'published' 
             ORDER BY published_at DESC LIMIT 10"
        );
        
        $this->render('frontend.home', [
            'posts' => $posts,
            'title' => $this->settings['site_title'] ?? 'Home',
            'description' => $this->settings['site_description'] ?? ''
        ]);
    }

    public function page(string $slug): void
    {
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published'",
            [$slug]
        );
        
        if (!$page) {
            throw new HttpException(404, 'Page not found');
        }
        
        $this->renderPage($page);
    }

    public function resolvePage(string $uri): bool
    {
        $slug = ltrim($uri, '/');
        
        if (empty($slug)) {
            return false;
        }
        
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published'",
            [$slug]
        );
        
        if ($page) {
            $this->renderPage($page);
            return true;
        }
        
        $segments = explode('/', $slug);
        
        if (count($segments) > 1) {
            $page = $this->db->fetch(
                "SELECT * FROM pages WHERE slug = ? AND status = 'published'",
                [$segments[count($segments) - 1]]
            );
            
            if ($page && $this->verifyPagePath($page, $segments)) {
                $this->renderPage($page);
                return true;
            }
        }
        
        return false;
    }

    private function verifyPagePath(array $page, array $segments): bool
    {
        $parentId = $page['parent_id'];
        $pathSegments = array_slice($segments, 0, -1);
        
        for ($i = count($pathSegments) - 1; $i >= 0; $i--) {
            if (!$parentId) {
                return false;
            }
            
            $parent = $this->db->fetch(
                "SELECT id, parent_id, slug FROM pages WHERE id = ?",
                [$parentId]
            );
            
            if (!$parent || $parent['slug'] !== $pathSegments[$i]) {
                return false;
            }
            
            $parentId = $parent['parent_id'];
        }
        
        return true;
    }

    private function renderPage(array $page): void
    {
        $structure = json_decode($page['structure'] ?? '{}', true);
        $content = $this->renderStructure($structure);
        
        $this->db->query(
            "UPDATE pages SET views = views + 1 WHERE id = ?",
            [$page['id']]
        );
        
        $seo = [
            'title' => $page['meta_title'] ?: $page['title'],
            'description' => $page['meta_description'] ?: '',
            'keywords' => $page['meta_keywords'] ?: '',
            'og_image' => $page['og_image'] ?: ''
        ];
        
        $this->render('frontend.page', [
            'page' => $page,
            'content' => $content,
            'structure' => $structure,
            'seo' => $seo,
            'title' => $page['title']
        ]);
    }

    public function post(string $slug): void
    {
        $post = $this->db->fetch(
            "SELECT p.*, c.name as category_name, c.slug as category_slug,
                    u.name as author_name
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.slug = ? AND p.status = 'published'",
            [$slug]
        );
        
        if (!$post) {
            throw new HttpException(404, 'Post not found');
        }
        
        $structure = json_decode($post['structure'] ?? '{}', true);
        $content = $this->renderStructure($structure);
        
        $this->db->query(
            "UPDATE posts SET views = views + 1 WHERE id = ?",
            [$post['id']]
        );
        
        $relatedPosts = $this->db->fetchAll(
            "SELECT * FROM posts 
             WHERE status = 'published' 
             AND category_id = ? 
             AND id != ?
             ORDER BY published_at DESC 
             LIMIT 3",
            [$post['category_id'], $post['id']]
        );
        
        $seo = [
            'title' => $post['meta_title'] ?: $post['title'],
            'description' => $post['meta_description'] ?: Helper::excerpt($post['content'] ?? ''),
            'keywords' => $post['meta_keywords'] ?: '',
            'og_image' => $post['featured_image'] ?: ''
        ];
        
        $this->render('frontend.post', [
            'post' => $post,
            'content' => $content,
            'structure' => $structure,
            'relatedPosts' => $relatedPosts,
            'seo' => $seo,
            'title' => $post['title']
        ]);
    }

    public function category(string $slug): void
    {
        $category = $this->db->fetch(
            "SELECT * FROM categories WHERE slug = ?",
            [$slug]
        );
        
        if (!$category) {
            throw new HttpException(404, 'Category not found');
        }
        
        $page = (int) $this->input('page', 1);
        $perPage = (int) ($this->settings['posts_per_page'] ?? 10);
        $offset = ($page - 1) * $perPage;
        
        $posts = $this->db->fetchAll(
            "SELECT * FROM posts 
             WHERE status = 'published' AND category_id = ?
             ORDER BY published_at DESC
             LIMIT ? OFFSET ?",
            [$category['id'], $perPage, $offset]
        );
        
        $totalPosts = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM posts WHERE status = 'published' AND category_id = ?",
            [$category['id']]
        );
        
        $this->render('frontend.category', [
            'category' => $category,
            'posts' => $posts,
            'pagination' => [
                'current' => $page,
                'per_page' => $perPage,
                'total' => $totalPosts,
                'last_page' => ceil($totalPosts / $perPage)
            ],
            'title' => $category['name']
        ]);
    }

    public function search(): void
    {
        $query = trim($this->input('q', ''));
        $results = [];
        
        if (strlen($query) >= 2) {
            $searchTerm = '%' . $query . '%';
            
            $pages = $this->db->fetchAll(
                "SELECT 'page' as type, id, title, slug, 
                        SUBSTR(content, 1, 200) as excerpt
                 FROM pages 
                 WHERE status = 'published' 
                 AND (title LIKE ? OR content LIKE ?)
                 ORDER BY title ASC
                 LIMIT 10",
                [$searchTerm, $searchTerm]
            );
            
            $posts = $this->db->fetchAll(
                "SELECT 'post' as type, id, title, slug, 
                        SUBSTR(content, 1, 200) as excerpt
                 FROM posts 
                 WHERE status = 'published' 
                 AND (title LIKE ? OR content LIKE ?)
                 ORDER BY published_at DESC
                 LIMIT 10",
                [$searchTerm, $searchTerm]
            );
            
            $results = array_merge($pages, $posts);
        }
        
        $this->render('frontend.search', [
            'query' => $query,
            'results' => $results,
            'title' => 'Search: ' . $query
        ]);
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        
        $pages = $this->db->fetchAll(
            "SELECT slug, updated_at FROM pages WHERE status = 'published'"
        );
        
        $posts = $this->db->fetchAll(
            "SELECT slug, updated_at FROM posts WHERE status = 'published'"
        );
        
        $baseUrl = $this->settings['site_url'] ?? Helper::url();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        echo '<url>';
        echo '<loc>' . htmlspecialchars($baseUrl) . '</loc>';
        echo '<changefreq>daily</changefreq>';
        echo '<priority>1.0</priority>';
        echo '</url>';
        
        foreach ($pages as $page) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/page/' . $page['slug']) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($page['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.8</priority>';
            echo '</url>';
        }
        
        foreach ($posts as $post) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/post/' . $post['slug']) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($post['updated_at'])) . '</lastmod>';
            echo '<changefreq>monthly</changefreq>';
            echo '<priority>0.6</priority>';
            echo '</url>';
        }
        
        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        $baseUrl = $this->settings['site_url'] ?? Helper::url();
        
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /api/\n";
        echo "\n";
        echo "Sitemap: {$baseUrl}/sitemap.xml\n";
    }

    private function renderStructure(array $structure): string
    {
        if (empty($structure)) {
            return '';
        }
        
        $html = '';
        
        if (!isset($structure['type'])) {
            if (isset($structure[0])) {
                foreach ($structure as $component) {
                    $html .= $this->renderComponent($component);
                }
            }
            return $html;
        }
        
        return $this->renderComponent($structure);
    }

    private function renderComponent(array $component): string
    {
        $type = $component['type'] ?? 'div';
        $class = $component['class'] ?? '';
        $content = $component['content'] ?? '';
        $children = $component['children'] ?? [];
        $attrs = $component['attributes'] ?? [];
        
        $attrStr = '';
        foreach ($attrs as $key => $value) {
            $attrStr .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        switch ($type) {
            case 'container':
                $html = '<div class="container ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                foreach ($children as $child) {
                    $html .= $this->renderComponent($child);
                }
                $html .= '</div>';
                break;
                
            case 'row':
                $html = '<div class="row ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                foreach ($children as $child) {
                    $html .= $this->renderComponent($child);
                }
                $html .= '</div>';
                break;
                
            case 'column':
                $cols = $component['cols'] ?? 12;
                $breakpoint = $component['breakpoint'] ?? 'md';
                $colClass = "col-{$breakpoint}-{$cols}";
                $html = '<div class="' . $colClass . ' ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                foreach ($children as $child) {
                    $html .= $this->renderComponent($child);
                }
                $html .= '</div>';
                break;
                
            case 'heading':
                $level = $component['level'] ?? 1;
                $html = '<h' . $level . ' class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= htmlspecialchars($content);
                $html .= '</h' . $level . '>';
                break;
                
            case 'paragraph':
                $html = '<p class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= nl2br(htmlspecialchars($content));
                $html .= '</p>';
                break;
                
            case 'text':
                $html = '<div class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= $content;
                $html .= '</div>';
                break;
                
            case 'image':
                $src = $component['src'] ?? '';
                $alt = $component['alt'] ?? '';
                $html = '<img src="' . htmlspecialchars($src) . '" ';
                $html .= 'alt="' . htmlspecialchars($alt) . '" ';
                $html .= 'class="img-fluid ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                break;
                
            case 'link':
                $href = $component['href'] ?? '#';
                $target = $component['target'] ?? '';
                $html = '<a href="' . htmlspecialchars($href) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '"';
                if ($target) {
                    $html .= ' target="' . htmlspecialchars($target) . '"';
                }
                $html .= $attrStr . '>';
                $html .= htmlspecialchars($content);
                $html .= '</a>';
                break;
                
            case 'button':
                $href = $component['href'] ?? '#';
                $btnClass = $component['btn_class'] ?? 'btn-primary';
                $html = '<a href="' . htmlspecialchars($href) . '" ';
                $html .= 'class="btn ' . $btnClass . ' ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= htmlspecialchars($content);
                $html .= '</a>';
                break;
                
            case 'card':
                $html = '<div class="card ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                if (!empty($component['image'])) {
                    $html .= '<img src="' . htmlspecialchars($component['image']) . '" class="card-img-top" alt="">';
                }
                $html .= '<div class="card-body">';
                if (!empty($component['title'])) {
                    $html .= '<h5 class="card-title">' . htmlspecialchars($component['title']) . '</h5>';
                }
                if (!empty($component['text'])) {
                    $html .= '<p class="card-text">' . htmlspecialchars($component['text']) . '</p>';
                }
                foreach ($children as $child) {
                    $html .= $this->renderComponent($child);
                }
                $html .= '</div>';
                if (!empty($component['footer'])) {
                    $html .= '<div class="card-footer">' . htmlspecialchars($component['footer']) . '</div>';
                }
                $html .= '</div>';
                break;
                
            case 'list':
                $items = $component['items'] ?? [];
                $ordered = $component['ordered'] ?? false;
                $tag = $ordered ? 'ol' : 'ul';
                $html = '<' . $tag . ' class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                foreach ($items as $item) {
                    $html .= '<li>' . htmlspecialchars($item) . '</li>';
                }
                $html .= '</' . $tag . '>';
                break;
                
            case 'divider':
                $html = '<hr class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                break;
                
            case 'spacer':
                $height = $component['height'] ?? '2rem';
                $html = '<div style="height: ' . htmlspecialchars($height) . ';" class="' . htmlspecialchars($class) . '"' . $attrStr . '></div>';
                break;
                
            case 'video':
                $src = $component['src'] ?? '';
                $html = '<div class="ratio ratio-16x9 ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                if (strpos($src, 'youtube.com') !== false || strpos($src, 'youtu.be') !== false) {
                    $html .= '<iframe src="' . htmlspecialchars($src) . '" allowfullscreen></iframe>';
                } else {
                    $html .= '<video src="' . htmlspecialchars($src) . '" controls></video>';
                }
                $html .= '</div>';
                break;
                
            case 'gallery':
                $images = $component['images'] ?? [];
                $html = '<div class="gallery ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= '<div class="row g-3">';
                foreach ($images as $image) {
                    $html .= '<div class="col-md-4">';
                    $html .= '<img src="' . htmlspecialchars($image['src'] ?? '') . '" ';
                    $html .= 'alt="' . htmlspecialchars($image['alt'] ?? '') . '" class="img-fluid rounded">';
                    $html .= '</div>';
                }
                $html .= '</div></div>';
                break;
                
            case 'alert':
                $alertType = $component['alert_type'] ?? 'info';
                $dismissible = $component['dismissible'] ?? false;
                $alertClass = "alert alert-{$alertType}";
                if ($dismissible) {
                    $alertClass .= ' alert-dismissible fade show';
                }
                $html = '<div class="' . $alertClass . ' ' . htmlspecialchars($class) . '"' . $attrStr . ' role="alert">';
                $html .= htmlspecialchars($content);
                if ($dismissible) {
                    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                }
                $html .= '</div>';
                break;
                
            case 'badge':
                $badgeType = $component['badge_type'] ?? 'primary';
                $html = '<span class="badge bg-' . $badgeType . ' ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= htmlspecialchars($content);
                $html .= '</span>';
                break;
                
            case 'quote':
                $author = $component['author'] ?? '';
                $html = '<blockquote class="blockquote ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= '<p class="mb-0">' . htmlspecialchars($content) . '</p>';
                if ($author) {
                    $html .= '<footer class="blockquote-footer">' . htmlspecialchars($author) . '</footer>';
                }
                $html .= '</blockquote>';
                break;
                
            case 'code':
                $language = $component['language'] ?? '';
                $html = '<pre class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= '<code class="language-' . htmlspecialchars($language) . '">';
                $html .= htmlspecialchars($content);
                $html .= '</code></pre>';
                break;
                
            case 'accordion':
                $items = $component['items'] ?? [];
                $id = $component['id'] ?? 'accordion-' . uniqid();
                $html = '<div class="accordion ' . htmlspecialchars($class) . '" id="' . $id . '"' . $attrStr . '>';
                foreach ($items as $i => $item) {
                    $itemId = $id . '-' . $i;
                    $show = ($item['expanded'] ?? false) ? 'show' : '';
                    $html .= '<div class="accordion-item">';
                    $html .= '<h2 class="accordion-header">';
                    $html .= '<button class="accordion-button ' . ($show ? '' : 'collapsed') . '" ';
                    $html .= 'type="button" data-bs-toggle="collapse" data-bs-target="#' . $itemId . '">';
                    $html .= htmlspecialchars($item['title'] ?? '');
                    $html .= '</button></h2>';
                    $html .= '<div id="' . $itemId . '" class="accordion-collapse collapse ' . $show . '" ';
                    $html .= 'data-bs-parent="#' . $id . '">';
                    $html .= '<div class="accordion-body">' . htmlspecialchars($item['content'] ?? '') . '</div>';
                    $html .= '</div></div>';
                }
                $html .= '</div>';
                break;
                
            case 'tabs':
                $tabs = $component['tabs'] ?? [];
                $id = $component['id'] ?? 'tabs-' . uniqid();
                $html = '<div class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= '<ul class="nav nav-tabs" id="' . $id . '-tabs" role="tablist">';
                foreach ($tabs as $i => $tab) {
                    $active = $i === 0 ? 'active' : '';
                    $selected = $i === 0 ? 'true' : 'false';
                    $html .= '<li class="nav-item" role="presentation">';
                    $html .= '<button class="nav-link ' . $active . '" data-bs-toggle="tab" ';
                    $html .= 'data-bs-target="#' . $id . '-' . $i . '" role="tab" aria-selected="' . $selected . '">';
                    $html .= htmlspecialchars($tab['title'] ?? '');
                    $html .= '</button></li>';
                }
                $html .= '</ul>';
                $html .= '<div class="tab-content" id="' . $id . '-content">';
                foreach ($tabs as $i => $tab) {
                    $active = $i === 0 ? 'show active' : '';
                    $html .= '<div class="tab-pane fade ' . $active . '" id="' . $id . '-' . $i . '" role="tabpanel">';
                    $html .= htmlspecialchars($tab['content'] ?? '');
                    $html .= '</div>';
                }
                $html .= '</div></div>';
                break;
                
            case 'table':
                $headers = $component['headers'] ?? [];
                $rows = $component['rows'] ?? [];
                $html = '<div class="table-responsive ' . htmlspecialchars($class) . '"' . $attrStr . '>';
                $html .= '<table class="table">';
                if (!empty($headers)) {
                    $html .= '<thead><tr>';
                    foreach ($headers as $header) {
                        $html .= '<th>' . htmlspecialchars($header) . '</th>';
                    }
                    $html .= '</tr></thead>';
                }
                $html .= '<tbody>';
                foreach ($rows as $row) {
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
                break;
                
            case 'form':
                $action = $component['action'] ?? '';
                $method = $component['method'] ?? 'POST';
                $fields = $component['fields'] ?? [];
                $html = '<form action="' . htmlspecialchars($action) . '" method="' . htmlspecialchars($method) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                foreach ($fields as $field) {
                    $html .= $this->renderFormField($field);
                }
                $html .= '</form>';
                break;
                
            case 'section':
                $id = $component['id'] ?? '';
                $html = '<section';
                if ($id) {
                    $html .= ' id="' . htmlspecialchars($id) . '"';
                }
                $html .= ' class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                foreach ($children as $child) {
                    $html .= $this->renderComponent($child);
                }
                $html .= '</section>';
                break;
                
            default:
                $html = '<div class="' . htmlspecialchars($class) . '"' . $attrStr . '>';
                if (is_string($content)) {
                    $html .= htmlspecialchars($content);
                }
                foreach ($children as $child) {
                    $html .= $this->renderComponent($child);
                }
                $html .= '</div>';
        }
        
        return $html;
    }

    private function renderFormField(array $field): string
    {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $required = $field['required'] ?? false;
        $class = $field['class'] ?? 'form-control';
        $id = $field['id'] ?? 'field-' . $name;
        
        $html = '<div class="mb-3">';
        
        if ($label) {
            $html .= '<label for="' . htmlspecialchars($id) . '" class="form-label">';
            $html .= htmlspecialchars($label);
            if ($required) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';
        }
        
        switch ($type) {
            case 'textarea':
                $html .= '<textarea id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '"';
                if ($required) {
                    $html .= ' required';
                }
                $html .= '>' . htmlspecialchars($field['value'] ?? '') . '</textarea>';
                break;
                
            case 'select':
                $options = $field['options'] ?? [];
                $html .= '<select id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '"';
                if ($required) {
                    $html .= ' required';
                }
                $html .= '>';
                foreach ($options as $value => $label) {
                    $selected = ($field['value'] ?? '') == $value ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>';
                    $html .= htmlspecialchars($label);
                    $html .= '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'checkbox':
            case 'radio':
                $html = '<div class="form-check">';
                $html .= '<input type="' . $type . '" id="' . htmlspecialchars($id) . '" ';
                $html .= 'name="' . htmlspecialchars($name) . '" ';
                $html .= 'class="form-check-input"';
                if ($required) {
                    $html .= ' required';
                }
                if (!empty($field['checked'])) {
                    $html .= ' checked';
                }
                $html .= '>';
                if ($label) {
                    $html .= '<label for="' . htmlspecialchars($id) . '" class="form-check-label">';
                    $html .= htmlspecialchars($label);
                    $html .= '</label>';
                }
                $html .= '</div>';
                break;
                
            default:
                $html .= '<input type="' . htmlspecialchars($type) . '" ';
                $html .= 'id="' . htmlspecialchars($id) . '" ';
                $html .= 'name="' . htmlspecialchars($name) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '" ';
                $html .= 'value="' . htmlspecialchars($field['value'] ?? '') . '"';
                $html .= ' placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '"';
                if ($required) {
                    $html .= ' required';
                }
                $html .= '>';
        }
        
        if (!empty($field['help'])) {
            $html .= '<div class="form-text">' . htmlspecialchars($field['help']) . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
