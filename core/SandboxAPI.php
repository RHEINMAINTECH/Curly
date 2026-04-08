<?php
/**
 * Sandbox API - Safe interface for extensions
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class SandboxAPI
{
    private Sandbox $sandbox;
    private Database $db;
    private ?AIService $ai;

    public function __construct(Sandbox $sandbox)
    {
        $this->sandbox = $sandbox;
        $this->db = App::getInstance()->getDatabase();
        $this->ai = App::getInstance()->getAI();
    }

    // ============ Database Operations (Read-Only) ============

    public function getPages(array $options = []): array
    {
        $sql = "SELECT * FROM pages WHERE status = 'published'";
        
        if (!empty($options['parent_id'])) {
            $sql .= " AND parent_id = " . (int) $options['parent_id'];
        }
        
        $sql .= " ORDER BY sort_order ASC";
        
        return $this->db->fetchAll($sql);
    }

    public function getPage(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE id = ? AND status = 'published'",
            [$id]
        );
    }

    public function getPageBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published'",
            [$slug]
        );
    }

    public function getPosts(array $options = []): array
    {
        $sql = "SELECT * FROM posts WHERE status = 'published'";
        
        if (!empty($options['category_id'])) {
            $sql .= " AND category_id = " . (int) $options['category_id'];
        }
        
        $limit = (int) ($options['limit'] ?? 10);
        $offset = (int) ($options['offset'] ?? 0);
        
        $sql .= " ORDER BY published_at DESC LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql);
    }

    public function getPost(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM posts WHERE id = ? AND status = 'published'",
            [$id]
        );
    }

    public function getPostBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM posts WHERE slug = ? AND status = 'published'",
            [$slug]
        );
    }

    public function getSettings(): array
    {
        $settings = [];
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        return $settings;
    }

    public function getSetting(string $key, $default = null)
    {
        $result = $this->db->fetch(
            "SELECT `value` FROM settings WHERE `key` = ?",
            [$key]
        );
        
        return $result ? $result['value'] : $default;
    }

    public function getCategories(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM categories ORDER BY name ASC"
        );
    }

    public function getMedia(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM media ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public function getMenus(): array
    {
        $menus = $this->db->fetchAll("SELECT * FROM menus ORDER BY name ASC");
        
        foreach ($menus as &$menu) {
            $menu['items'] = $this->db->fetchAll(
                "SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC",
                [$menu['id']]
            );
        }
        
        return $menus;
    }

    // ============ AI Operations ============

    public function aiGenerate(string $prompt, array $options = []): ?string
    {
        if (!$this->ai) {
            return null;
        }
        
        return $this->ai->generate($prompt, $options);
    }

    public function aiGenerateStructure(string $type, string $description): ?array
    {
        if (!$this->ai) {
            return null;
        }
        
        return $this->ai->generateStructure($type, $description);
    }

    public function aiOptimizeContent(string $content, array $keywords = []): ?string
    {
        if (!$this->ai) {
            return null;
        }
        
        return $this->ai->optimizeSEO($content, $keywords);
    }

    // ============ JSON Structure Operations ============

    public function getStructure(string $type, string $id): ?array
    {
        $file = CMS_STORAGE . "/structures/{$type}/{$id}.json";
        
        if (!file_exists($file)) {
            return null;
        }
        
        return json_decode(file_get_contents($file), true);
    }

    public function renderStructure(array $structure): string
    {
        return $this->renderBootstrapStructure($structure);
    }

    private function renderBootstrapStructure(array $structure): string
    {
        $html = '';
        
        if (!isset($structure['type'])) {
            return $html;
        }
        
        switch ($structure['type']) {
            case 'container':
                $class = $structure['class'] ?? 'container';
                $html .= '<div class="' . htmlspecialchars($class) . '">';
                if (isset($structure['children'])) {
                    foreach ($structure['children'] as $child) {
                        $html .= $this->renderBootstrapStructure($child);
                    }
                }
                $html .= '</div>';
                break;
                
            case 'row':
                $class = $structure['class'] ?? 'row';
                $html .= '<div class="' . htmlspecialchars($class) . '">';
                if (isset($structure['children'])) {
                    foreach ($structure['children'] as $child) {
                        $html .= $this->renderBootstrapStructure($child);
                    }
                }
                $html .= '</div>';
                break;
                
            case 'column':
                $cols = $structure['cols'] ?? 12;
                $breakpoint = $structure['breakpoint'] ?? 'md';
                $class = $structure['class'] ?? "col-{$breakpoint}-{$cols}";
                $html .= '<div class="' . htmlspecialchars($class) . '">';
                if (isset($structure['children'])) {
                    foreach ($structure['children'] as $child) {
                        $html .= $this->renderBootstrapStructure($child);
                    }
                }
                $html .= '</div>';
                break;
                
            case 'heading':
                $level = $structure['level'] ?? 1;
                $content = $structure['content'] ?? '';
                $class = $structure['class'] ?? '';
                $html .= '<h' . $level . ' class="' . htmlspecialchars($class) . '">';
                $html .= htmlspecialchars($content);
                $html .= '</h' . $level . '>';
                break;
                
            case 'paragraph':
                $content = $structure['content'] ?? '';
                $class = $structure['class'] ?? '';
                $html .= '<p class="' . htmlspecialchars($class) . '">';
                $html .= nl2br(htmlspecialchars($content));
                $html .= '</p>';
                break;
                
            case 'image':
                $src = $structure['src'] ?? '';
                $alt = $structure['alt'] ?? '';
                $class = $structure['class'] ?? 'img-fluid';
                $html .= '<img src="' . htmlspecialchars($src) . '" ';
                $html .= 'alt="' . htmlspecialchars($alt) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '">';
                break;
                
            case 'link':
                $href = $structure['href'] ?? '#';
                $content = $structure['content'] ?? '';
                $class = $structure['class'] ?? '';
                $target = $structure['target'] ?? '';
                $html .= '<a href="' . htmlspecialchars($href) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '"';
                if ($target) {
                    $html .= ' target="' . htmlspecialchars($target) . '"';
                }
                $html .= '>' . htmlspecialchars($content) . '</a>';
                break;
                
            case 'button':
                $content = $structure['content'] ?? '';
                $class = $structure['class'] ?? 'btn btn-primary';
                $onclick = $structure['onclick'] ?? '';
                $html .= '<button class="' . htmlspecialchars($class) . '"';
                if ($onclick) {
                    $html .= ' onclick="' . htmlspecialchars($onclick) . '"';
                }
                $html .= '>' . htmlspecialchars($content) . '</button>';
                break;
                
            case 'card':
                $class = $structure['class'] ?? 'card';
                $html .= '<div class="' . htmlspecialchars($class) . '">';
                if (isset($structure['header'])) {
                    $html .= '<div class="card-header">' . htmlspecialchars($structure['header']) . '</div>';
                }
                if (isset($structure['body'])) {
                    $html .= '<div class="card-body">';
                    if (isset($structure['title'])) {
                        $html .= '<h5 class="card-title">' . htmlspecialchars($structure['title']) . '</h5>';
                    }
                    if (isset($structure['text'])) {
                        $html .= '<p class="card-text">' . htmlspecialchars($structure['text']) . '</p>';
                    }
                    if (isset($structure['children'])) {
                        foreach ($structure['children'] as $child) {
                            $html .= $this->renderBootstrapStructure($child);
                        }
                    }
                    $html .= '</div>';
                }
                if (isset($structure['footer'])) {
                    $html .= '<div class="card-footer">' . htmlspecialchars($structure['footer']) . '</div>';
                }
                $html .= '</div>';
                break;
                
            case 'list':
                $items = $structure['items'] ?? [];
                $ordered = $structure['ordered'] ?? false;
                $class = $structure['class'] ?? '';
                $tag = $ordered ? 'ol' : 'ul';
                $html .= '<' . $tag . ' class="' . htmlspecialchars($class) . '">';
                foreach ($items as $item) {
                    $html .= '<li>' . htmlspecialchars($item) . '</li>';
                }
                $html .= '</' . $tag . '>';
                break;
                
            case 'html':
                // Raw HTML - only allow if explicitly permitted
                if ($structure['safe'] ?? false) {
                    $html .= $structure['content'] ?? '';
                }
                break;
                
            case 'component':
                // Custom component rendering
                $componentName = $structure['name'] ?? '';
                $props = $structure['props'] ?? [];
                $html .= $this->renderComponent($componentName, $props);
                break;
        }
        
        return $html;
    }

    private function renderComponent(string $name, array $props): string
    {
        $componentFile = CMS_ROOT . "/themes/default/components/{$name}.php";
        
        if (!file_exists($componentFile)) {
            return "<!-- Component not found: {$name} -->";
        }
        
        ob_start();
        extract($props);
        include $componentFile;
        return ob_get_clean();
    }

    // ============ Utility Methods ============

    public function log(string $message, string $level = 'info'): void
    {
        $logFile = CMS_STORAGE . '/logs/extension.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [{$level}] {$message}\n";
        
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public function getCache(): Cache
    {
        return App::getInstance()->getCache();
    }

    public function view(string $template, array $data = []): string
    {
        $view = new View();
        return $view->render($template, $data);
    }
}
