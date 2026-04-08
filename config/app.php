<?php
/**
 * Application Configuration
 */

return [
    'name' => getenv('APP_NAME') ?: 'Curly CMS',
    'version' => '1.0.0',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'Europe/Berlin',
    'locale' => 'en',
    
    'session' => [
        'driver' => getenv('SESSION_DRIVER') ?: 'file',
        'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 7200),
        'name' => 'curlycms_session'
    ],
    
    'cache' => [
        'driver' => getenv('CACHE_DRIVER') ?: 'file',
        'lifetime' => (int) (getenv('CACHE_LIFETIME') ?: 3600),
        'path' => CMS_STORAGE . '/cache'
    ],
    
    'uploads' => [
        'path' => CMS_ROOT . '/public/uploads',
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx'],
        'image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ],
    
    'mail' => [
        'driver' => getenv('MAIL_DRIVER') ?: 'smtp',
        'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'port' => (int) (getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Curly CMS'
    ],
    
    'theme' => [
        'active' => 'default',
        'path' => CMS_ROOT . '/themes'
    ]
];
