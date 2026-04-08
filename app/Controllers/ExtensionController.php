<?php
/**
 * Extension Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\Sandbox;
use CurlyCMS\Core\HttpException;

class ExtensionController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $extensionPath = CMS_ROOT . '/extensions';
        $extensions = [];
        
        if (is_dir($extensionPath)) {
            $dirs = glob($extensionPath . '/*', GLOB_ONLYDIR);
            
            foreach ($dirs as $dir) {
                $name = basename($dir);
                $manifestFile = $dir . '/manifest.json';
                
                if (file_exists($manifestFile)) {
                    $manifest = json_decode(file_get_contents($manifestFile), true);
                    
                    if ($manifest) {
                        // Check if installed in database
                        $installed = $this->db->fetch(
                            "SELECT * FROM extensions WHERE name = ?",
                            [$name]
                        );
                        
                        $extensions[$name] = [
                            'name' => $name,
                            'title' => $manifest['title'] ?? $name,
                            'version' => $manifest['version'] ?? '1.0.0',
                            'description' => $manifest['description'] ?? '',
                            'author' => $manifest['author'] ?? '',
                            'permissions' => $manifest['permissions'] ?? [],
                            'installed' => $installed !== null,
                            'active' => $installed ? (bool) $installed['active'] : false,
                            'installed_at' => $installed['installed_at'] ?? null
                        ];
                    }
                }
            }
        }
        
        $this->render('backend.extensions.index', [
            'extensions' => $extensions,
            'title' => 'Extensions'
        ]);
    }

    public function install(string $name): void
    {
        $extensionDir = CMS_ROOT . '/extensions/' . $name;
        $manifestFile = $extensionDir . '/manifest.json';
        
        if (!file_exists($manifestFile)) {
            throw new HttpException(404, 'Extension not found');
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        
        if (!$manifest) {
            throw new HttpException(500, 'Invalid extension manifest');
        }
        
        // Check if already installed
        $existing = $this->db->fetch(
            "SELECT id FROM extensions WHERE name = ?",
            [$name]
        );
        
        if ($existing) {
            $this->session->flash('error', 'Extension is already installed.');
            $this->redirect('/admin/extensions');
            return;
        }
        
        // Run install script if exists
        $installScript = $extensionDir . '/install.php';
        if (file_exists($installScript)) {
            $sandbox = new Sandbox($extensionDir);
            try {
                $sandbox->executeFile($installScript, [
                    'db' => $this->db,
                    'manifest' => $manifest
                ]);
            } catch (\Throwable $e) {
                $this->session->flash('error', 'Installation failed: ' . $e->getMessage());
                $this->redirect('/admin/extensions');
                return;
            }
        }
        
        // Register in database
        $this->db->insert('extensions', [
            'name' => $name,
            'version' => $manifest['version'] ?? '1.0.0',
            'active' => 0,
            'settings' => json_encode($manifest['settings'] ?? []),
            'installed_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->session->flash('success', 'Extension installed successfully.');
        $this->redirect('/admin/extensions');
    }

    public function activate(string $name): void
    {
        $extension = $this->db->fetch(
            "SELECT * FROM extensions WHERE name = ?",
            [$name]
        );
        
        if (!$extension) {
            throw new HttpException(404, 'Extension not found');
        }
        
        $extensionDir = CMS_ROOT . '/extensions/' . $name;
        
        // Run activation script if exists
        $activateScript = $extensionDir . '/activate.php';
        if (file_exists($activateScript)) {
            $sandbox = new Sandbox($extensionDir);
            try {
                $sandbox->executeFile($activateScript);
            } catch (\Throwable $e) {
                $this->session->flash('error', 'Activation failed: ' . $e->getMessage());
                $this->redirect('/admin/extensions');
                return;
            }
        }
        
        $this->db->update('extensions', [
            'active' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['name' => $name]);
        
        $this->session->flash('success', 'Extension activated successfully.');
        $this->redirect('/admin/extensions');
    }

    public function deactivate(string $name): void
    {
        $extension = $this->db->fetch(
            "SELECT * FROM extensions WHERE name = ?",
            [$name]
        );
        
        if (!$extension) {
            throw new HttpException(404, 'Extension not found');
        }
        
        $extensionDir = CMS_ROOT . '/extensions/' . $name;
        
        // Run deactivation script if exists
        $deactivateScript = $extensionDir . '/deactivate.php';
        if (file_exists($deactivateScript)) {
            $sandbox = new Sandbox($extensionDir);
            try {
                $sandbox->executeFile($deactivateScript);
            } catch (\Throwable $e) {
                // Log but continue
                error_log('Extension deactivation warning: ' . $e->getMessage());
            }
        }
        
        $this->db->update('extensions', [
            'active' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['name' => $name]);
        
        $this->session->flash('success', 'Extension deactivated successfully.');
        $this->redirect('/admin/extensions');
    }

    public function uninstall(string $name): void
    {
        $extension = $this->db->fetch(
            "SELECT * FROM extensions WHERE name = ?",
            [$name]
        );
        
        if (!$extension) {
            throw new HttpException(404, 'Extension not found');
        }
        
        $extensionDir = CMS_ROOT . '/extensions/' . $name;
        
        // Run uninstall script if exists
        $uninstallScript = $extensionDir . '/uninstall.php';
        if (file_exists($uninstallScript)) {
            $sandbox = new Sandbox($extensionDir);
            try {
                $sandbox->executeFile($uninstallScript, [
                    'db' => $this->db
                ]);
            } catch (\Throwable $e) {
                // Log but continue
                error_log('Extension uninstall warning: ' . $e->getMessage());
            }
        }
        
        $this->db->delete('extensions', ['name' => $name]);
        
        $this->session->flash('success', 'Extension uninstalled successfully.');
        $this->redirect('/admin/extensions');
    }
}
