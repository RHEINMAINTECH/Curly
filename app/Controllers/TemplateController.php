<?php
/**
 * Template Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\HttpException;

class TemplateController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $templates = $this->db->fetchAll(
            "SELECT * FROM templates ORDER BY name ASC"
        );
        
        foreach ($templates as &$template) {
            $template['structure'] = json_decode($template['structure'] ?? '{}', true);
        }
        
        $this->render('backend.templates.index', [
            'templates' => $templates,
            'title' => 'Templates'
        ]);
    }

    public function show(int $id): void
    {
        $template = $this->db->fetch(
            "SELECT * FROM templates WHERE id = ?",
            [$id]
        );
        
        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }
        
        $template['structure'] = json_decode($template['structure'] ?? '{}', true);
        
        $this->json([
            'success' => true,
            'template' => $template
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|min:2'
        ]);
        
        if (!$validation['valid']) {
            $this->json(['errors' => $validation['errors']], 400);
            return;
        }
        
        $structure = $this->input('structure');
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }
        
        $id = $this->db->insert('templates', [
            'name' => $this->input('name'),
            'description' => $this->input('description', ''),
            'type' => $this->input('type', 'page'),
            'structure' => json_encode($structure),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->json([
            'success' => true,
            'id' => $id
        ]);
    }

    public function update(int $id): void
    {
        $template = $this->db->fetch(
            "SELECT id FROM templates WHERE id = ?",
            [$id]
        );
        
        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }
        
        $structure = $this->input('structure');
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }
        
        $this->db->update('templates', [
            'name' => $this->input('name'),
            'description' => $this->input('description', ''),
            'type' => $this->input('type', 'page'),
            'structure' => json_encode($structure),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
        
        $this->json([
            'success' => true
        ]);
    }

    public function destroy(int $id): void
    {
        $template = $this->db->fetch(
            "SELECT id FROM templates WHERE id = ?",
            [$id]
        );
        
        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }
        
        $this->db->delete('templates', ['id' => $id]);
        
        $this->json([
            'success' => true
        ]);
    }
}
