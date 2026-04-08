<?php
/**
 * Example Extension Routes
 * 
 * Custom routes for the extension.
 * 
 * @var \CurlyCMS\Core\SandboxAPI $cms
 */

function index($cms) {
    // Get data using the sandbox API
    $pages = $cms->getPages();
    
    // Render a view
    $html = '<h1>Example Extension</h1>';
    $html .= '<p>This is a custom route from the example extension.</p>';
    $html .= '<h2>Pages:</h2><ul>';
    
    foreach ($pages as $page) {
        $html .= '<li>' . htmlspecialchars($page['title']) . '</li>';
    }
    
    $html .= '</ul>';
    
    echo $html;
}
