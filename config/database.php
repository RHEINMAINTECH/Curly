<?php
/**
 * Database Configuration
 */

return [
    'driver' => getenv('DB_CONNECTION') ?: 'sqlite',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE') ?: 'curlycms',
    'username' => getenv('DB_USERNAME') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'path' => CMS_STORAGE . '/database/cms.db'
];
