<?php
/**
 * Setting Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;

class SettingController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $settings = [];
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        $themes = $this->getAvailableThemes();
        
        $this->render('backend.settings.index', [
            'settings' => $settings,
            'themes' => $themes,
            'title' => 'Settings'
        ]);
    }

    public function update(): void
    {
        $settings = $this->input('settings', []);
        
        foreach ($settings as $key => $value) {
            // Check if setting exists
            $existing = $this->db->fetch(
                "SELECT id FROM settings WHERE `key` = ?",
                [$key]
            );
            
            if ($existing) {
                $this->db->update('settings', ['value' => $value], ['key' => $key]);
            } else {
                $this->db->insert('settings', [
                    'key' => $key,
                    'value' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        $this->session->flash('success', 'Settings saved successfully.');
        $this->redirect('/admin/settings');
    }

    private function getAvailableThemes(): array
    {
        $themes = [];
        $themeDir = CMS_ROOT . '/themes';
        
        if (is_dir($themeDir)) {
            $dirs = glob($themeDir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $name = basename($dir);
                $themeFile = $dir . '/theme.json';
                
                if (file_exists($themeFile)) {
                    $manifest = json_decode(file_get_contents($themeFile), true);
                    $themes[$name] = [
                        'name' => $manifest['name'] ?? $name,
                        'version' => $manifest['version'] ?? '1.0.0',
                        'description' => $manifest['description'] ?? ''
                    ];
                } else {
                    $themes[$name] = [
                        'name' => ucfirst($name),
                        'version' => '1.0.0',
                        'description' => ''
                    ];
                }
            }
        }
        
        return $themes;
    }
}
