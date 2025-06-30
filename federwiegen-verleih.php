 <?php
 /**
  * Plugin Name: Federwiegen Verleih
  * Plugin URI: https://kleinehelden-verleih.de
  * Description: Ein Plugin für den Verleih von Federwiegen mit konfigurierbaren Produkten und Stripe-Integration
  * Version: 1.5.1
  * Author: Kleine Helden
  * License: GPL v2 or later
  * Text Domain: federwiegen-verleih
  */
 
declare(strict_types=1);

 if (!defined('ABSPATH')) {
     exit;
 }
 
// Plugin constants
const FEDERWIEGEN_PLUGIN_VERSION = '1.5.1';
const FEDERWIEGEN_PLUGIN_DIR = __DIR__ . '/';
const FEDERWIEGEN_PLUGIN_URL = plugin_dir_url(__FILE__);
define('FEDERWIEGEN_PLUGIN_PATH', FEDERWIEGEN_PLUGIN_DIR);
define('FEDERWIEGEN_VERSION', FEDERWIEGEN_PLUGIN_VERSION);
define('FEDERWIEGEN_PLUGIN_FILE', __FILE__);
 

require_once FEDERWIEGEN_PLUGIN_DIR . 'includes/class-autoloader.php';
FederwiegenVerleih\Autoloader::register();

// Initialize plugin
new FederwiegenVerleih\Plugin();
