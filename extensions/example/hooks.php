<?php
/**
 * Example Extension Hooks
 * 
 * Register hooks for various CMS events.
 * 
 * @var \CurlyCMS\Core\SandboxAPI $cms
 */

// This file is executed when hooks are triggered
// The $cms variable provides safe access to CMS functionality

// Example: Log when a page is rendered
$cms->log('Example extension: Page rendered', 'info');

// Return data to be passed to the page
return [
    'example_message' => 'This is injected by the example extension!'
];
