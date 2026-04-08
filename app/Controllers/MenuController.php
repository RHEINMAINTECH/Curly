<?php
/**
 * Menu Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\HttpException;

class MenuController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $menus = $this->db->fetchAll(
            "SELECT * FROM menus ORDER BY name ASC"
        );
        
        foreach ($menus as &$menu) {
            $menu['items'] = $this->getMenuItems((int) $menu['id']);
        }
        
        $pages = $this->db->fetchAll(
            "SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title ASC"
        );
        
        $this->render('backend.menus.index', [
            'menus' => $menus,
            'pages' => $pages,
            'title' => 'Menus'
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|min:2',
            'slug' => 'required|min:2'
        ]);
        
        if (!$validation['valid']) {
            $this->withErrors($validation['errors']);
            $this->redirect('/admin/menus');
            return;
        }
        
        $menuId = $this->db->insert('menus', [
            'name' => $this->input('name'),
            'slug' => $this->input('slug'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Save menu items
        $items = json_decode($this->input('items', '[]'), true);
        if (is_array($items)) {
            $this->saveMenuItems($menuId, $items);
        }
        
        $this->session->flash('success', 'Menu created successfully.');
        $this->redirect('/admin/menus');
    }

    public function update(int $id): void
    {
        $menu = $this->db->fetch(
            "SELECT id FROM menus WHERE id = ?",
            [$id]
        );
        
        if (!$menu) {
            throw new HttpException(404, 'Menu not found');
        }
        
        $this->db->update('menus', [
            'name' => $this->input('name'),
            'slug' => $this->input('slug'),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
        
        // Delete existing items
        $this->db->delete('menu_items', ['menu_id' => $id]);
        
        // Save new items
        $items = json_decode($this->input('items', '[]'), true);
        if (is_array($items)) {
            $this->saveMenuItems($id, $items);
        }
        
        $this->session->flash('success', 'Menu updated successfully.');
        $this->redirect('/admin/menus');
    }

    public function destroy(int $id): void
    {
        $menu = $this->db->fetch(
            "SELECT id FROM menus WHERE id = ?",
            [$id]
        );
        
        if (!$menu) {
            throw new HttpException(404, 'Menu not found');
        }
        
        // Delete menu items first
        $this->db->delete('menu_items', ['menu_id' => $id]);
        
        // Delete menu
        $this->db->delete('menus', ['id' => $id]);
        
        $this->session->flash('success', 'Menu deleted successfully.');
        $this->redirect('/admin/menus');
    }

    private function getMenuItems(int $menuId, int $parentId = 0): array
    {
        $items = $this->db->fetchAll(
            "SELECT * FROM menu_items WHERE menu_id = ? AND parent_id = ? ORDER BY sort_order ASC",
            [$menuId, $parentId]
        );
        
        foreach ($items as &$item) {
            $item['children'] = $this->getMenuItems($menuId, (int) $item['id']);
        }
        
        return $items;
    }

    private function saveMenuItems(int $menuId, array $items, int $parentId = 0): void
    {
        foreach ($items as $sortOrder => $item) {
            $itemId = $this->db->insert('menu_items', [
                'menu_id' => $menuId,
                'parent_id' => $parentId,
                'title' => $item['title'] ?? '',
                'url' => $item['url'] ?? '',
                'page_id' => (int) ($item['page_id'] ?? 0) ?: null,
                'target' => $item['target'] ?? '',
                'class' => $item['class'] ?? '',
                'sort_order' => $sortOrder,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Save children
            if (!empty($item['children']) && is_array($item['children'])) {
                $this->saveMenuItems($menuId, $item['children'], (int) $itemId);
            }
        }
    }
}
