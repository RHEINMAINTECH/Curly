<?php
/**
 * Curly CMS - Public Entry Point
 * 
 * @package CurlyCMS
 * @author RheinMainTech GmbH
 */

declare(strict_types=1);

// Define path to application root
define('CMS_ROOT', dirname(__DIR__));

// Include the main entry point from the root
require CMS_ROOT . '/index.php';

