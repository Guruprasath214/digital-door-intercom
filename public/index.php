<?php
/**
 * QR Intercom - Main Entry Point
 */

// Load configuration
require_once dirname(__FILE__) . '/../src/config/config.php';

// Load router
require_once CONTROLLERS_PATH . '/../config/Router.php';

// Initialize router and handle request
$router = new Router();
$router->route();
?>
