<?php
/**
 * Security Configuration
 */

return [
    'salt' => getenv('SECURITY_SALT') ?: 'change-this-to-a-random-string-in-production',
    'cipher' => getenv('SECURITY_CIPHER') ?: 'aes-256-gcm',
    'csrf_enabled' => true,
    'csrf_token_name' => '_token',
    
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => false
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'login_attempts' => 5,
        'login_decay' => 15, // minutes
        'api_requests' => 100,
        'api_decay' => 1 // minute
    ],
    
    'headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';"
    ],
    
    'allowed_file_types' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'json', 'xml'
    ],
    
    'max_upload_size' => 10 * 1024 * 1024 // 10MB
];
