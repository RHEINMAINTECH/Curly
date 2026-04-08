<?php
/**
 * PSR-4 Autoloader
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class Autoloader
{
    private array $prefixes = [];
    private string $root;

    public function __construct()
    {
        $this->root = CMS_ROOT;
        
        // Register CMS namespaces
        $this->addNamespace('CurlyCMS\\Core', $this->root . '/core');
        $this->addNamespace('CurlyCMS\\App', $this->root . '/app');
        $this->addNamespace('CurlyCMS\\Extensions', $this->root . '/extensions');
    }

    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }
        
        $this->prefixes[$prefix][] = $baseDir;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    public function loadClass(string $class): bool
    {
        $prefix = $class;
        
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return true;
            }
            
            $prefix = rtrim($prefix, '\\');
        }
        
        return false;
    }

    private function loadMappedFile(string $prefix, string $relativeClass): bool
    {
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }
        
        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require $file;
                return true;
            }
        }
        
        return false;
    }
}
