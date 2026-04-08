<?php
/**
 * Extensions Configuration
 */

return [
    'enabled' => true,
    'path' => CMS_ROOT . '/extensions',
    'sandbox_enabled' => true,
    
    'auto_discover' => true,
    
    'core_extensions' => [
        // Core extensions that cannot be disabled
    ],
    
    'permissions' => [
        'read_database' => false,
        'write_database' => false,
        'read_files' => true,
        'write_files' => false,
        'network_access' => false,
        'execute_code' => false
    ],
    
    'hooks' => [
        'before_page_render',
        'after_page_render',
        'before_post_save',
        'after_post_save',
        'before_user_login',
        'after_user_login',
        'on_extension_load',
        'on_extension_unload'
    ]
];

I'll continue the implementation starting from the incomplete FrontendController.php file.
