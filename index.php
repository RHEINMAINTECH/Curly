<?php
/**
 * Curly CMS - Main Entry Point
 * 
 * @package CurlyCMS
 * @author RheinMainTech GmbH
 * @version 1.0.0
 */

declare(strict_types=1);

// Application constants
define('CMS_ROOT', dirname(__FILE__));
define('CMS_CORE', CMS_ROOT . '/core');
define('CMS_APP', CMS_ROOT . '/app');
define('CMS_STORAGE', CMS_ROOT . '/storage');
define('CMS_PUBLIC', CMS_ROOT . '/public');
define('CMS_VERSION', '1.0.0');
define('CMS_START', microtime(true));

// Error reporting based on environment
if (getenv('APP_DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', CMS_STORAGE . '/logs/error.log');
}

// Autoloader
require_once CMS_CORE . '/Autoloader.php';
$autoloader = new \CurlyCMS\Core\Autoloader();
$autoloader->register();

// Load environment
$envFile = CMS_ROOT . '/.env';
if (file_exists($envFile)) {
    \CurlyCMS\Core\Helper::loadEnv($envFile);
}

// Initialize application
try {
    $app = new \CurlyCMS\Core\App();
    $app->run();
} catch (Exception $e) {
    http_response_code(500);
    error_log('[CurlyCMS Fatal] ' . $e->getMessage());
    
    if (getenv('APP_DEBUG') === 'true') {
        echo '<h1>Application Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Internal Server Error</h1>';
        echo '<p>The application encountered an error. Please try again later.</p>';
    }
}
