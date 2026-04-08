<?php
/**
 * Example Extension Installation Script
 * 
 * This script runs when the extension is installed.
 * Use it to create database tables, default settings, etc.
 * 
 * @var \CurlyCMS\Core\Database $db
 * @var array $manifest
 */

// Create example table
$db->query("
    CREATE TABLE IF NOT EXISTS example_data (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Insert default data
$db->insert('example_data', [
    'title' => 'Welcome to Example Extension',
    'content' => 'This is example data created during installation.'
]);

// Add settings
$db->insert('settings', [
    'key' => 'example_enabled',
    'value' => '1',
    'created_at' => date('Y-m-d H:i:s')
]);

return true;
