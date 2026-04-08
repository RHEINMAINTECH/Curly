<?php
/**
 * Curly CMS Installer
 * 
 * Run this script once to initialize the CMS
 * 
 * @package CurlyCMS\Install
 */

declare(strict_types=1);

// Prevent running from web if not CLI
if (php_sapi_name() !== 'cli' && !isset($_GET['install_key'])) {
    die('This script must be run from CLI or with an install key.');
}

define('CMS_ROOT', dirname(__DIR__));
define('CMS_STORAGE', CMS_ROOT . '/storage');

// Load autoloader
require_once CMS_ROOT . '/core/Autoloader.php';
$autoloader = new \CurlyCMS\Core\Autoloader();
$autoloader->register();

// Load environment
$envFile = CMS_ROOT . '/.env';
if (file_exists($envFile)) {
    \CurlyCMS\Core\Helper::loadEnv($envFile);
}

use CurlyCMS\Core\Database;
use CurlyCMS\Core\Security;

echo "=================================\n";
echo "Curly CMS Installer v1.0.0\n";
echo "RheinMainTech GmbH\n";
echo "=================================\n\n";

// Check requirements
echo "Checking requirements...\n";

$requirements = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO SQLite' => extension_loaded('pdo_sqlite'),
    'JSON Extension' => extension_loaded('json'),
    'Mbstring Extension' => extension_loaded('mbstring'),
    'OpenSSL Extension' => extension_loaded('openssl'),
    'Curl Extension' => extension_loaded('curl'),
    'Storage Writable' => is_writable(CMS_ROOT) || is_writable(CMS_STORAGE)
];

$failed = false;
foreach ($requirements as $name => $passed) {
    echo "  - {$name}: " . ($passed ? "\033[32mOK\033[0m" : "\033[31mFAILED\033[0m") . "\n";
    if (!$passed) {
        $failed = true;
    }
}

if ($failed) {
    echo "\n\033[31mSome requirements are not met. Please fix them before continuing.\033[0m\n";
    exit(1);
}

echo "\n\033[32mAll requirements met!\033[0m\n\n";

// Create directories
echo "Creating directories...\n";

$directories = [
    CMS_STORAGE,
    CMS_STORAGE . '/cache',
    CMS_STORAGE . '/logs',
    CMS_STORAGE . '/database',
    CMS_STORAGE . '/uploads',
    CMS_STORAGE . '/ai',
    CMS_STORAGE . '/ai/templates',
    CMS_STORAGE . '/structures',
    CMS_STORAGE . '/structures/pages',
    CMS_STORAGE . '/structures/posts',
    CMS_ROOT . '/public/uploads'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "  Created: {$dir}\n";
    } else {
        echo "  Exists: {$dir}\n";
    }
}

echo "\n";

// Initialize database
echo "Initializing database...\n";

$dbPath = CMS_STORAGE . '/database/cms.db';
$config = [
    'driver' => 'sqlite',
    'path' => $dbPath
];

try {
    $db = new Database($config);
    
    // Load schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new \RuntimeException("Schema file not found: {$schemaFile}");
    }
    
    $schema = file_get_contents($schemaFile);
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->query($statement);
        }
    }
    
    echo "  Database schema created.\n";
    
} catch (\Throwable $e) {
    echo "\033[31mDatabase error: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}

// Load seed data
echo "Loading seed data...\n";

$seedFile = __DIR__ . '/seed.sql';
if (file_exists($seedFile)) {
    $seed = file_get_contents($seedFile);
    $statements = array_filter(array_map('trim', explode(';', $seed)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, 'INSERT') !== false) {
            try {
                $db->query($statement);
            } catch (\Throwable $e) {
                // Ignore duplicate key errors during re-install
            }
        }
    }
    
    echo "  Seed data loaded.\n";
}

// Create admin user
echo "\nCreating admin user...\n";

$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';

// Generate proper password hash
$security = new Security();
$hashedPassword = $security->hash($adminPassword);

// Update or create admin user
$existingAdmin = $db->fetch("SELECT id FROM users WHERE email = ?", [$adminEmail]);

if ($existingAdmin) {
    $db->update('users', [
        'password' => $hashedPassword
    ], ['email' => $adminEmail]);
    echo "  Admin password updated.\n";
} else {
    $db->insert('users', [
        'name' => 'Admin',
        'email' => $adminEmail,
        'password' => $hashedPassword,
        'role' => 'admin',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    echo "  Admin user created.\n";
}

echo "\n  Email: {$adminEmail}\n";
echo "  Password: {$adminPassword}\n";
echo "\n  \033[33mIMPORTANT: Change these credentials after first login!\033[0m\n";

// Generate application key
echo "\nGenerating application key...\n";

$appKey = bin2hex(random_bytes(32));
$envFile = CMS_ROOT . '/.env';
$envContent = '';

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (strpos($envContent, 'APP_KEY=') !== false) {
        $envContent = preg_replace('/APP_KEY=.*/', "APP_KEY={$appKey}", $envContent);
    } else {
        $envContent .= "\nAPP_KEY={$appKey}\n";
    }
} else {
    $envContent = "APP_KEY={$appKey}\n";
}

file_put_contents($envFile, $envContent);
echo "  Application key generated.\n";

// Generate API keys
echo "\nGenerating API keys...\n";

$apiKey = bin2hex(random_bytes(32));
$mcsToken = bin2hex(random_bytes(32));
$a2aToken = bin2hex(random_bytes(32));
$webhookSecret = bin2hex(random_bytes(32));

$db->update('api_keys', ['key' => $apiKey], ['name' => 'Default API Key']);
$db->update('mcs_tokens', ['token' => $mcsToken], ['name' => 'Default MCS Token']);
$db->update('a2a_tokens', ['token' => $a2aToken], ['agent_id' => 'cms-agent']);

echo "  API Key: {$apiKey}\n";
echo "  MCS Token: {$mcsToken}\n";
echo "  A2A Token: {$a2aToken}\n";
echo "  Webhook Secret: {$webhookSecret}\n";

// Update .env with generated secrets
$envContent .= "WEBHOOK_SECRET={$webhookSecret}\n";
file_put_contents($envFile, $envContent);

// Set file permissions
echo "\nSetting file permissions...\n";

$writableDirs = [
    CMS_STORAGE,
    CMS_STORAGE . '/cache',
    CMS_STORAGE . '/logs',
    CMS_STORAGE . '/database',
    CMS_STORAGE . '/uploads',
    CMS_ROOT . '/public/uploads'
];

foreach ($writableDirs as $dir) {
    if (is_dir($dir)) {
        chmod($dir, 0755);
        echo "  Set permissions: {$dir}\n";
    }
}

// Create .htaccess for public directory
$htaccessFile = CMS_ROOT . '/public/.htaccess';
if (!file_exists($htaccessFile)) {
    $htaccess = <<<'HTACCESS'
# Curly CMS Public Directory .htaccess
RewriteEngine On
RewriteBase /

# Force HTTPS (uncomment in production)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove trailing slashes
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [L,R=301]

# Serve existing files
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# Route to index.php
RewriteRule ^ index.php [L]

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Set default charset
AddDefaultCharset UTF-8

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json
</IfModule>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
HTACCESS;
    
    file_put_contents($htaccessFile, $htaccess);
    echo "  Created: .htaccess\n";
}

// Create public index.php for production
$publicIndexFile = CMS_ROOT . '/public/index.php';
if (!file_exists($publicIndexFile)) {
    $publicIndex = <<<'PHP'
<?php
/**
 * Curly CMS - Public Entry Point
 */

// Define path to application root
define('CMS_ROOT', dirname(__DIR__));

// Include main application
require CMS_ROOT . '/index.php';
PHP;
    
    file_put_contents($publicIndexFile, $publicIndex);
    echo "  Created: public/index.php\n";
}

// Installation complete
echo "\n=================================\n";
echo "\033[32mInstallation Complete!\033[0m\n";
echo "=================================\n\n";

echo "Next steps:\n";
echo "1. Configure your web server to point to the 'public' directory\n";
echo "2. Visit /admin to access the backend\n";
echo "3. Log in with the admin credentials shown above\n";
echo "4. Change the default password immediately\n";
echo "5. Configure your AI provider API key in .env\n";
echo "6. Update site settings in the admin panel\n\n";

echo "Documentation: https://curlycms.readthedocs.io\n";
echo "Support: support@rheinmaintech.de\n\n";

echo "\033[32mThank you for choosing Curly CMS!\033[0m\n";
