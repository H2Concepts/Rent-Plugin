<?php
 /**
  * Plugin Name: Federwiegen Verleih
  * Plugin URI: https://kleinehelden-verleih.de
  * Description: Ein Plugin für den Verleih von Federwiegen mit konfigurierbaren Produkten und Stripe-Integration
  * Version: 1.5.2
  * Author: Kleine Helden
  * License: GPL v2 or later
  * Text Domain: federwiegen-verleih
  */
 
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
const FEDERWIEGEN_PLUGIN_VERSION = '1.5.2';
const FEDERWIEGEN_PLUGIN_DIR = __DIR__ . '/';
define('FEDERWIEGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FEDERWIEGEN_PLUGIN_PATH', FEDERWIEGEN_PLUGIN_DIR);
define('FEDERWIEGEN_VERSION', FEDERWIEGEN_PLUGIN_VERSION);
define('FEDERWIEGEN_PLUGIN_FILE', __FILE__);

// Control whether default demo data is inserted on activation
if (!defined('FEDERWIEGEN_LOAD_DEFAULT_DATA')) {
    define('FEDERWIEGEN_LOAD_DEFAULT_DATA', true);
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
