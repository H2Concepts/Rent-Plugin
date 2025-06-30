<?php
namespace FederwiegenVerleih;

class Plugin {
    private $db;
    private $ajax;
    private $admin;

    public function __construct() {
        $this->db = new Database();
        $this->ajax = new Ajax();
        $this->admin = new Admin();

        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'check_for_updates']);
        add_action('wp_head', [$this, 'add_meta_tags']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        add_action('wp_head', [$this, 'add_open_graph_tags']);
    }

    public function init() {
        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_shortcode('federwiegen_product', [$this, 'product_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_assets']);

        add_action('wp_ajax_get_product_price', [$this->ajax, 'ajax_get_product_price']);
        add_action('wp_ajax_nopriv_get_product_price', [$this->ajax, 'ajax_get_product_price']);
        add_action('wp_ajax_get_variant_images', [$this->ajax, 'ajax_get_variant_images']);
        add_action('wp_ajax_nopriv_get_variant_images', [$this->ajax, 'ajax_get_variant_images']);
        add_action('wp_ajax_get_extra_image', [$this->ajax, 'ajax_get_extra_image']);
        add_action('wp_ajax_nopriv_get_extra_image', [$this->ajax, 'ajax_get_extra_image']);
        add_action('wp_ajax_track_interaction', [$this->ajax, 'ajax_track_interaction']);
        add_action('wp_ajax_nopriv_track_interaction', [$this->ajax, 'ajax_track_interaction']);
        add_action('wp_ajax_get_variant_options', [$this->ajax, 'ajax_get_variant_options']);
        add_action('wp_ajax_nopriv_get_variant_options', [$this->ajax, 'ajax_get_variant_options']);
        add_action('wp_ajax_submit_order', [$this->ajax, 'ajax_submit_order']);
        add_action('wp_ajax_nopriv_submit_order', [$this->ajax, 'ajax_submit_order']);

        add_filter('admin_footer_text', [$this->admin, 'custom_admin_footer']);
        add_action('admin_head', [$this->admin, 'custom_admin_styles']);
    }

    public function check_for_updates() {
        $current_version = get_option('federwiegen_version', '1.0.0');
        if (version_compare($current_version, FEDERWIEGEN_VERSION, '<')) {
            $this->db->update_database();
            update_option('federwiegen_version', FEDERWIEGEN_VERSION);
        }
    }

    public function activate() {
        $this->db->create_tables();
        $load_sample = defined('FEDERWIEGEN_LOAD_DEFAULT_DATA') ? FEDERWIEGEN_LOAD_DEFAULT_DATA : false;
        $load_sample = apply_filters('federwiegen_load_default_data', $load_sample);
        if ($load_sample) {
            $this->db->insert_default_data();
        }
        update_option('federwiegen_version', FEDERWIEGEN_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public static function activate_plugin() {
        $plugin = new self();
        $plugin->activate();
    }

    public static function deactivate_plugin() {
        $plugin = new self();
        $plugin->deactivate();
    }
