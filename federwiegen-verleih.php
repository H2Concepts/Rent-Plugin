<?php
 /**
  * Plugin Name: Rent Plugin
  * Plugin URI: https://h2concepts.de
  * Description: Ein Plugin für den Verleih von Waren mit konfigurierbaren Produkten und Stripe-Integration
 * Version: 2.6.5
  * Author: H2 Concepts
  * License: GPL v2 or later
  * Text Domain: h2-concepts
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
if (!defined('FEDERWIEGEN_PLUGIN_VERSION')) {
    define('FEDERWIEGEN_PLUGIN_VERSION', '2.6.5');
}
if (!defined('FEDERWIEGEN_PLUGIN_DIR')) {
    define('FEDERWIEGEN_PLUGIN_DIR', __DIR__ . '/');
}
if (!defined('FEDERWIEGEN_PLUGIN_URL')) {
    define('FEDERWIEGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('FEDERWIEGEN_PLUGIN_PATH')) {
    define('FEDERWIEGEN_PLUGIN_PATH', FEDERWIEGEN_PLUGIN_DIR);
}
if (!defined('FEDERWIEGEN_VERSION')) {
    define('FEDERWIEGEN_VERSION', FEDERWIEGEN_PLUGIN_VERSION);
}
if (!defined('FEDERWIEGEN_PLUGIN_FILE')) {
    define('FEDERWIEGEN_PLUGIN_FILE', __FILE__);
}

// Control whether default demo data is inserted on activation
if (!defined('FEDERWIEGEN_LOAD_DEFAULT_DATA')) {
    define('FEDERWIEGEN_LOAD_DEFAULT_DATA', false);
}

require_once FEDERWIEGEN_PLUGIN_DIR . 'includes/Autoloader.php';
FederwiegenVerleih\Autoloader::register();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['FederwiegenVerleih\\Plugin', 'activate_plugin']);
register_deactivation_hook(__FILE__, ['FederwiegenVerleih\\Plugin', 'deactivate_plugin']);

// Initialize the plugin after WordPress has loaded
add_action('plugins_loaded', function () {
    new \FederwiegenVerleih\Plugin();
});
