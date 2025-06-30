<?php


namespace FederwiegenVerleih;

class Plugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Add update check
        add_action('plugins_loaded', array($this, 'check_for_updates'));
        
        // SEO and Meta features
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('wp_head', array($this, 'add_schema_markup'));
        add_action('wp_head', array($this, 'add_open_graph_tags'));
    }
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register shortcode
        add_shortcode('federwiegen_product', array($this, 'product_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_get_product_price', array($this, 'ajax_get_product_price'));
        add_action('wp_ajax_nopriv_get_product_price', array($this, 'ajax_get_product_price'));
        add_action('wp_ajax_get_variant_images', array($this, 'ajax_get_variant_images'));
        add_action('wp_ajax_nopriv_get_variant_images', array($this, 'ajax_get_variant_images'));
        add_action('wp_ajax_get_extra_image', array($this, 'ajax_get_extra_image'));
        add_action('wp_ajax_nopriv_get_extra_image', array($this, 'ajax_get_extra_image'));
        add_action('wp_ajax_track_interaction', array($this, 'ajax_track_interaction'));
        add_action('wp_ajax_nopriv_track_interaction', array($this, 'ajax_track_interaction'));
        add_action('wp_ajax_get_variant_options', array($this, 'ajax_get_variant_options'));
        add_action('wp_ajax_nopriv_get_variant_options', array($this, 'ajax_get_variant_options'));
        add_action('wp_ajax_submit_order', array($this, 'ajax_submit_order'));
        add_action('wp_ajax_nopriv_submit_order', array($this, 'ajax_submit_order'));
        
        // White-label features
        add_filter('admin_footer_text', array($this, 'custom_admin_footer'));
        add_action('admin_head', array($this, 'custom_admin_styles'));
    }
    
    public function check_for_updates() {
        $current_version = get_option('federwiegen_version', '1.0.0');
        if (version_compare($current_version, FEDERWIEGEN_VERSION, '<')) {
            $this->update_database();
            update_option('federwiegen_version', FEDERWIEGEN_VERSION);
        }
    }
    
    public function activate() {
        $this->create_tables();

        $load_sample = defined('FEDERWIEGEN_LOAD_DEFAULT_DATA') ?
            FEDERWIEGEN_LOAD_DEFAULT_DATA : true;
        $load_sample = apply_filters('federwiegen_load_default_data', $load_sample);

        if ($load_sample) {
            $this->insert_default_data();
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
    
    private function update_database() {
        global $wpdb;
        
        // Add category_id column to all tables if it doesn't exist
        $tables_to_update = array(
            'federwiegen_variants',
            'federwiegen_extras', 
            'federwiegen_durations',
            'federwiegen_links'
        );
        
        foreach ($tables_to_update as $table_suffix) {
            $table_name = $wpdb->prefix . $table_suffix;
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
            }
        }
        
        // Add image columns to variants table if they don't exist
        $table_variants = $wpdb->prefix . 'federwiegen_variants';
        $columns_to_add = array(
            'image_url_1' => 'TEXT',
            'image_url_2' => 'TEXT',
            'image_url_3' => 'TEXT',
            'image_url_4' => 'TEXT',
            'image_url_5' => 'TEXT',
            'available' => 'TINYINT(1) DEFAULT 1',
            'availability_note' => 'VARCHAR(255) DEFAULT ""'
        );
        
        foreach ($columns_to_add as $column => $type) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_variants ADD COLUMN $column $type AFTER base_price");
            }
        }
        
        // Remove old single image_url column if it exists
        $old_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'image_url'");
        if (!empty($old_column_exists)) {
            // Migrate data from old column to new column
            $wpdb->query("UPDATE $table_variants SET image_url_1 = image_url WHERE image_url IS NOT NULL AND image_url != ''");
            $wpdb->query("ALTER TABLE $table_variants DROP COLUMN image_url");
        }
        
        // Add image_url column to extras table if it doesn't exist
        $table_extras = $wpdb->prefix . 'federwiegen_extras';
        $extra_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_extras LIKE 'image_url'");
        if (empty($extra_column_exists)) {
            $wpdb->query("ALTER TABLE $table_extras ADD COLUMN image_url TEXT AFTER price");
        }
        
        // Create categories table if it doesn't exist
        $table_categories = $wpdb->prefix . 'federwiegen_categories';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_categories'");
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_categories (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                shortcode varchar(100) NOT NULL UNIQUE,
                page_title varchar(255) DEFAULT '',
                page_description text DEFAULT '',
                meta_title varchar(255) DEFAULT '',
                meta_description text DEFAULT '',
                product_title varchar(255) DEFAULT '',
                product_description text DEFAULT '',
                default_image text DEFAULT '',
                features_title varchar(255) DEFAULT 'Warum unsere Federwiegen?',
                feature_1_icon text DEFAULT '',
                feature_1_title varchar(255) DEFAULT '',
                feature_1_description text DEFAULT '',
                feature_2_icon text DEFAULT '',
                feature_2_title varchar(255) DEFAULT '',
                feature_2_description text DEFAULT '',
                feature_3_icon text DEFAULT '',
                feature_3_title varchar(255) DEFAULT '',
                feature_3_description text DEFAULT '',
                button_text varchar(255) DEFAULT '',
                button_icon text DEFAULT '',
                shipping_cost decimal(10,2) DEFAULT 9.99,
                layout_style varchar(50) DEFAULT 'default',
                duration_tooltip text DEFAULT '',
                condition_tooltip text DEFAULT '',
                active tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Add new columns to existing categories table
            $new_columns = array(
                'meta_title' => 'VARCHAR(255)',
                'meta_description' => 'TEXT',
                'features_title' => 'VARCHAR(255) DEFAULT "Warum unsere Federwiegen?"',
                'feature_1_icon' => 'TEXT',
                'feature_1_title' => 'VARCHAR(255)',
                'feature_1_description' => 'TEXT',
                'feature_2_icon' => 'TEXT',
                'feature_2_title' => 'VARCHAR(255)',
                'feature_2_description' => 'TEXT',
                'feature_3_icon' => 'TEXT',
                'feature_3_title' => 'VARCHAR(255)',
                'feature_3_description' => 'TEXT',
                'button_text' => 'VARCHAR(255)',
                'button_icon' => 'TEXT',
                'shipping_cost' => 'DECIMAL(10,2) DEFAULT 9.99',
                'layout_style' => 'VARCHAR(50) DEFAULT "default"',
                'duration_tooltip' => 'TEXT',
                'condition_tooltip' => 'TEXT'
            );
            
            foreach ($new_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_categories LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_categories ADD COLUMN $column $type");
                }
            }
        }
        
        // Create analytics table if it doesn't exist
        $table_analytics = $wpdb->prefix . 'federwiegen_analytics';
        $analytics_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_analytics'");
        
        if (!$analytics_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_analytics (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) NOT NULL,
                event_type varchar(50) NOT NULL,
                variant_id mediumint(9) DEFAULT NULL,
                extra_id mediumint(9) DEFAULT NULL,
                duration_id mediumint(9) DEFAULT NULL,
                condition_id mediumint(9) DEFAULT NULL,
                product_color_id mediumint(9) DEFAULT NULL,
                frame_color_id mediumint(9) DEFAULT NULL,
                user_ip varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Add new columns to analytics table
            $new_analytics_columns = array(
                'condition_id' => 'mediumint(9)',
                'product_color_id' => 'mediumint(9)',
                'frame_color_id' => 'mediumint(9)'
            );
            
            foreach ($new_analytics_columns as $column => $type) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_analytics LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_analytics ADD COLUMN $column $type AFTER duration_id");
                }
            }
        }
        
        // Create branding settings table if it doesn't exist
        $table_branding = $wpdb->prefix . 'federwiegen_branding';
        $branding_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_branding'");
        
        if (!$branding_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_branding (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                setting_key varchar(255) NOT NULL,
                setting_value longtext,
                PRIMARY KEY (id),
                UNIQUE KEY setting_key (setting_key)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Insert default branding settings
            $default_branding = array(
                'plugin_name' => 'Federwiegen Verleih',
                'plugin_description' => 'Ein Plugin für den Verleih von Federwiegen mit konfigurierbaren Produkten und Stripe-Integration',
                'company_name' => 'Kleine Helden',
                'company_url' => 'https://kleinehelden-verleih.de',
                'admin_logo' => '',
                'admin_color_primary' => '#5f7f5f',
                'admin_color_secondary' => '#4a674a',
                'footer_text' => 'Powered by Federwiegen Verleih Plugin'
            );
            
            foreach ($default_branding as $key => $value) {
                $wpdb->insert(
                    $table_branding,
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    )
                );
            }
        }
        
        // Create conditions table if it doesn't exist
        $table_conditions = $wpdb->prefix . 'federwiegen_conditions';
        $conditions_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_conditions'");
        
        if (!$conditions_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_conditions (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) DEFAULT 1,
                name varchar(255) NOT NULL,
                description text DEFAULT '',
                price_modifier decimal(5,4) DEFAULT 0,
                available tinyint(1) DEFAULT 1,
                active tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Create colors table if it doesn't exist
        $table_colors = $wpdb->prefix . 'federwiegen_colors';
        $colors_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_colors'");
        
        if (!$colors_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_colors (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) DEFAULT 1,
                name varchar(255) NOT NULL,
                color_code varchar(7) NOT NULL,
                color_type varchar(20) NOT NULL,
                available tinyint(1) DEFAULT 1,
                active tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Create variant options table if it doesn't exist
        $table_variant_options = $wpdb->prefix . 'federwiegen_variant_options';
        $variant_options_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_variant_options'");
        
        if (!$variant_options_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_variant_options (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                variant_id mediumint(9) NOT NULL,
                option_type varchar(50) NOT NULL,
                option_id mediumint(9) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY variant_option (variant_id, option_type, option_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Create orders table if it doesn't exist
        $table_orders = $wpdb->prefix . 'federwiegen_orders';
        $orders_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_orders'");
        
        if (!$orders_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_orders (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                category_id mediumint(9) NOT NULL,
                variant_id mediumint(9) NOT NULL,
                extra_id mediumint(9) NOT NULL,
                duration_id mediumint(9) NOT NULL,
                condition_id mediumint(9) DEFAULT NULL,
                product_color_id mediumint(9) DEFAULT NULL,
                frame_color_id mediumint(9) DEFAULT NULL,
                final_price decimal(10,2) NOT NULL,
                stripe_link text NOT NULL,
                customer_name varchar(255) DEFAULT '',
                customer_email varchar(255) DEFAULT '',
                user_ip varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Update links table with new columns
        $table_links = $wpdb->prefix . 'federwiegen_links';
        $new_link_columns = array(
            'condition_id' => 'mediumint(9)',
            'product_color_id' => 'mediumint(9)',
            'frame_color_id' => 'mediumint(9)'
        );
        
        foreach ($new_link_columns as $column => $type) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_links LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_links ADD COLUMN $column $type AFTER duration_id");
            }
        }
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Categories table for different product categories (with SEO fields)
        $table_categories = $wpdb->prefix . 'federwiegen_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            shortcode varchar(100) NOT NULL UNIQUE,
            page_title varchar(255) DEFAULT '',
            page_description text DEFAULT '',
            meta_title varchar(255) DEFAULT '',
            meta_description text DEFAULT '',
            product_title varchar(255) DEFAULT '',
            product_description text DEFAULT '',
            default_image text DEFAULT '',
            features_title varchar(255) DEFAULT 'Warum unsere Federwiegen?',
            feature_1_icon text DEFAULT '',
            feature_1_title varchar(255) DEFAULT '',
            feature_1_description text DEFAULT '',
            feature_2_icon text DEFAULT '',
            feature_2_title varchar(255) DEFAULT '',
            feature_2_description text DEFAULT '',
            feature_3_icon text DEFAULT '',
            feature_3_title varchar(255) DEFAULT '',
            feature_3_description text DEFAULT '',
            button_text varchar(255) DEFAULT '',
            button_icon text DEFAULT '',
            shipping_cost decimal(10,2) DEFAULT 9.99,
            layout_style varchar(50) DEFAULT 'default',
            duration_tooltip text DEFAULT '',
            condition_tooltip text DEFAULT '',
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Variants table (updated with multiple image fields, category_id and availability)
        $table_variants = $wpdb->prefix . 'federwiegen_variants';
        $sql_variants = "CREATE TABLE $table_variants (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            description text,
            base_price decimal(10,2) NOT NULL,
            image_url_1 text,
            image_url_2 text,
            image_url_3 text,
            image_url_4 text,
            image_url_5 text,
            available tinyint(1) DEFAULT 1,
            availability_note varchar(255) DEFAULT '',
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Extras table (updated with image field and category_id)
        $table_extras = $wpdb->prefix . 'federwiegen_extras';
        $sql_extras = "CREATE TABLE $table_extras (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL,
            image_url text,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Durations table (with category_id)
        $table_durations = $wpdb->prefix . 'federwiegen_durations';
        $sql_durations = "CREATE TABLE $table_durations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            months_minimum int(11) NOT NULL,
            discount decimal(5,4) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Links table (with category_id and new option columns)
        $table_links = $wpdb->prefix . 'federwiegen_links';
        $sql_links = "CREATE TABLE $table_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            variant_id mediumint(9) NOT NULL,
            extra_id mediumint(9) NOT NULL,
            duration_id mediumint(9) NOT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            product_color_id mediumint(9) DEFAULT NULL,
            frame_color_id mediumint(9) DEFAULT NULL,
            stripe_link text NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY category_variant_extra_duration (category_id, variant_id, extra_id, duration_id, condition_id, product_color_id, frame_color_id)
        ) $charset_collate;";
        
        // Analytics table for tracking (with new option columns)
        $table_analytics = $wpdb->prefix . 'federwiegen_analytics';
        $sql_analytics = "CREATE TABLE $table_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) NOT NULL,
            event_type varchar(50) NOT NULL,
            variant_id mediumint(9) DEFAULT NULL,
            extra_id mediumint(9) DEFAULT NULL,
            duration_id mediumint(9) DEFAULT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            product_color_id mediumint(9) DEFAULT NULL,
            frame_color_id mediumint(9) DEFAULT NULL,
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Branding settings table
        $table_branding = $wpdb->prefix . 'federwiegen_branding';
        $sql_branding = "CREATE TABLE $table_branding (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        // Conditions table
        $table_conditions = $wpdb->prefix . 'federwiegen_conditions';
        $sql_conditions = "CREATE TABLE $table_conditions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            description text DEFAULT '',
            price_modifier decimal(5,4) DEFAULT 0,
            available tinyint(1) DEFAULT 1,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Colors table
        $table_colors = $wpdb->prefix . 'federwiegen_colors';
        $sql_colors = "CREATE TABLE $table_colors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) DEFAULT 1,
            name varchar(255) NOT NULL,
            color_code varchar(7) NOT NULL,
            color_type varchar(20) NOT NULL,
            available tinyint(1) DEFAULT 1,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Variant options table
        $table_variant_options = $wpdb->prefix . 'federwiegen_variant_options';
        $sql_variant_options = "CREATE TABLE $table_variant_options (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            variant_id mediumint(9) NOT NULL,
            option_type varchar(50) NOT NULL,
            option_id mediumint(9) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY variant_option (variant_id, option_type, option_id)
        ) $charset_collate;";
        
        // Orders table
        $table_orders = $wpdb->prefix . 'federwiegen_orders';
        $sql_orders = "CREATE TABLE $table_orders (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category_id mediumint(9) NOT NULL,
            variant_id mediumint(9) NOT NULL,
            extra_id mediumint(9) NOT NULL,
            duration_id mediumint(9) NOT NULL,
            condition_id mediumint(9) DEFAULT NULL,
            product_color_id mediumint(9) DEFAULT NULL,
            frame_color_id mediumint(9) DEFAULT NULL,
            final_price decimal(10,2) NOT NULL,
            stripe_link text NOT NULL,
            customer_name varchar(255) DEFAULT '',
            customer_email varchar(255) DEFAULT '',
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_categories);
        dbDelta($sql_variants);
        dbDelta($sql_extras);
        dbDelta($sql_durations);
        dbDelta($sql_links);
        dbDelta($sql_analytics);
        dbDelta($sql_branding);
        dbDelta($sql_conditions);
        dbDelta($sql_colors);
        dbDelta($sql_variant_options);
        dbDelta($sql_orders);
    }
    
    private function insert_default_data() {
        global $wpdb;
        
        // Insert default category
        $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_categories");
        if ($existing_categories == 0) {
            $wpdb->insert(
                $wpdb->prefix . 'federwiegen_categories',
                array(
                    'name' => 'Standard Federwiegen',
                    'shortcode' => 'federwiegen_product',
                    'page_title' => 'Federwiegen Verleih',
                    'page_description' => 'Mieten Sie unsere hochwertigen Federwiegen für einen entspannten Alltag mit Ihrem Baby.',
                    'meta_title' => 'Federwiegen mieten - Entspannter Schlaf für Ihr Baby',
                    'meta_description' => 'Hochwertige Federwiegen mieten ✓ Sichere Babywiegen ✓ Flexible Mietdauer ✓ Professionelle Reinigung ✓ Jetzt online buchen!',
                    'product_title' => 'Premium Federwiegen',
                    'product_description' => 'Unsere Federwiegen bieten Ihrem Baby die natürliche Wiegebewegung, die es aus dem Mutterleib kennt. Dies fördert einen ruhigen Schlaf und gibt Ihnen als Eltern wertvolle Erholung.',
                    'default_image' => '',
                    'features_title' => 'Warum unsere Federwiegen?',
                    'feature_1_icon' => '',
                    'feature_1_title' => 'Sicherheit First',
                    'feature_1_description' => 'Alle unsere Produkte sind nach höchsten Sicherheitsstandards geprüft und zertifiziert.',
                    'feature_2_icon' => '',
                    'feature_2_title' => 'Liebevolle Pflege',
                    'feature_2_description' => 'Jede Federwiege wird nach der Rückgabe professionell gereinigt und desinfiziert.',
                    'feature_3_icon' => '',
                    'feature_3_title' => 'Modern & Smart',
                    'feature_3_description' => 'Optional mit App-Steuerung für maximalen Komfort im Alltag.',
                    'button_text' => 'Jetzt Mieten',
                    'button_icon' => '',
                    'shipping_cost' => 9.99,
                    'layout_style' => 'default',
                    'duration_tooltip' => 'Nach der Mindestmietdauer kannst Du dein Abo jederzeit kündigen oder auf ein anderes Produkt wechseln.',
                    'condition_tooltip' => "Neu: Neue, originalverpackte Produkte.\n\nAufbereitet: Bereits benutzte Produkte, die von uns gereinigt, desinfiziert, repariert und in Top-Zustand gebracht wurden.\n\nWeitere Informationen findest du im Helpcenter.",
                    'sort_order' => 0
                )
            );
        }
        
        // Insert default branding settings
        $existing_branding = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_branding");
        if ($existing_branding == 0) {
            $default_branding = array(
                'plugin_name' => 'Federwiegen Verleih',
                'plugin_description' => 'Ein Plugin für den Verleih von Federwiegen mit konfigurierbaren Produkten und Stripe-Integration',
                'company_name' => 'Kleine Helden',
                'company_url' => 'https://kleinehelden-verleih.de',
                'admin_logo' => '',
                'admin_color_primary' => '#5f7f5f',
                'admin_color_secondary' => '#4a674a',
                'footer_text' => 'Powered by Federwiegen Verleih Plugin'
            );
            
            foreach ($default_branding as $key => $value) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_branding',
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    )
                );
            }
        }
        
        // Insert default variants only if table is empty
        $existing_variants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_variants");
        if ($existing_variants == 0) {
            $variants = array(
                array('Federwiege + Gestell & Motor', 'Komplettset mit stabilem Gestell und leisem Motor', 89.00),
                array('Federwiege + Türklammer & Motor', 'Platzsparende Lösung mit praktischer Türklammer', 79.00),
                array('Wiege + Gestell & Motor mit App-Steuerung', 'Premium-Variante mit smarter App-Steuerung', 119.00)
            );
            
            foreach ($variants as $index => $variant) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_variants',
                    array(
                        'category_id' => 1,
                        'name' => $variant[0],
                        'description' => $variant[1],
                        'base_price' => $variant[2],
                        'image_url_1' => '',
                        'image_url_2' => '',
                        'image_url_3' => '',
                        'image_url_4' => '',
                        'image_url_5' => '',
                        'available' => 1,
                        'availability_note' => '',
                        'sort_order' => $index
                    )
                );
            }
        }
        
        // Insert default extras only if table is empty
        $existing_extras = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_extras");
        if ($existing_extras == 0) {
            $extras = array(
                array('Kein Extra', 0.00),
                array('Himmel', 15.00)
            );
            
            foreach ($extras as $index => $extra) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_extras',
                    array(
                        'category_id' => 1,
                        'name' => $extra[0],
                        'price' => $extra[1],
                        'image_url' => '',
                        'sort_order' => $index
                    )
                );
            }
        }
        
        // Insert default durations only if table is empty
        $existing_durations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_durations");
        if ($existing_durations == 0) {
            $durations = array(
                array('Flexible Abo', 1, 0.00),
                array('ab 2+', 2, 0.05),
                array('ab 4+', 4, 0.10),
                array('ab 6+', 6, 0.15)
            );
            
            foreach ($durations as $index => $duration) {
                $wpdb->insert(
                    $wpdb->prefix . 'federwiegen_durations',
                    array(
                        'category_id' => 1,
                        'name' => $duration[0],
                        'months_minimum' => $duration[1],
                        'discount' => $duration[2],
                        'sort_order' => $index
                    )
                );
            }
        }
    }
    
    public function add_admin_menu() {
        $branding = $this->get_branding_settings();
        $menu_title = $branding['plugin_name'] ?? 'Federwiegen';
        
        add_menu_page(
            $branding['plugin_name'] ?? 'Federwiegen Verleih',
            $menu_title,
            'manage_options',
            'federwiegen-verleih',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );
        
        // Submenu: Kategorien
        add_submenu_page(
            'federwiegen-verleih',
            'Kategorien',
            'Kategorien',
            'manage_options',
            'federwiegen-categories',
            array($this, 'categories_page')
        );
        
        // Submenu: Produktverwaltung
        add_submenu_page(
            'federwiegen-verleih',
            'Ausführungen',
            'Ausführungen',
            'manage_options',
            'federwiegen-variants',
            array($this, 'variants_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Extras',
            'Extras',
            'manage_options',
            'federwiegen-extras',
            array($this, 'extras_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Mietdauer',
            'Mietdauer',
            'manage_options',
            'federwiegen-durations',
            array($this, 'durations_page')
        );
        
        // New submenu items
        add_submenu_page(
            'federwiegen-verleih',
            'Zustand',
            'Zustand',
            'manage_options',
            'federwiegen-conditions',
            array($this, 'conditions_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Farben',
            'Farben',
            'manage_options',
            'federwiegen-colors',
            array($this, 'colors_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Ausführungs-Optionen',
            'Ausführungs-Optionen',
            'manage_options',
            'federwiegen-variant-options',
            array($this, 'variant_options_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Stripe Links',
            'Stripe Links',
            'manage_options',
            'federwiegen-links',
            array($this, 'links_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Bestellungen',
            'Bestellungen',
            'manage_options',
            'federwiegen-orders',
            array($this, 'orders_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Analytics',
            'Analytics',
            'manage_options',
            'federwiegen-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Branding',
            'Branding',
            'manage_options',
            'federwiegen-branding',
            array($this, 'branding_page')
        );
        
        add_submenu_page(
            'federwiegen-verleih',
            'Debug',
            'Debug',
            'manage_options',
            'federwiegen-debug',
            array($this, 'debug_page')
        );
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('federwiegen-style', FEDERWIEGEN_PLUGIN_URL . 'assets/style.css', array(), FEDERWIEGEN_VERSION);
        wp_enqueue_script('federwiegen-script', FEDERWIEGEN_PLUGIN_URL . 'assets/script.js', array('jquery'), FEDERWIEGEN_VERSION, true);
        
        wp_localize_script('federwiegen-script', 'federwiegen_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('federwiegen_nonce')
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'federwiegen') !== false) {
            wp_enqueue_style('federwiegen-admin-style', FEDERWIEGEN_PLUGIN_URL . 'assets/admin-style.css', array(), FEDERWIEGEN_VERSION);
            wp_enqueue_script('federwiegen-admin-script', FEDERWIEGEN_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), FEDERWIEGEN_VERSION, true);
            
            // Enqueue WordPress media scripts for image upload
            wp_enqueue_media();
            
            // Enqueue Chart.js for analytics
            if (strpos($hook, 'federwiegen-analytics') !== false) {
                wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
            }
        }
    }
    
    public function product_shortcode($atts) {
        global $wpdb;
        
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'category' => '',
            'title' => '',
            'description' => ''
        ), $atts);
        
        // Determine category
        $category = null;
        if (!empty($atts['category'])) {
            // Find category by shortcode or name
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s OR name = %s AND active = 1",
                $atts['category'], $atts['category']
            ));
        }
        
        // If no category found, use default (first active category)
        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE active = 1 ORDER BY sort_order LIMIT 1");
        }
        
        // If still no category, return error
        if (!$category) {
            return '<p>Keine aktive Produktkategorie gefunden.</p>';
        }
        
        // Override with shortcode attributes if provided
        $page_title = !empty($atts['title']) ? $atts['title'] : $category->page_title;
        $page_description = !empty($atts['description']) ? $atts['description'] : $category->page_description;
        
        ob_start();
        include FEDERWIEGEN_PLUGIN_PATH . 'templates/product-page.php';
        return ob_get_clean();
    }
    
    // SEO and Meta functions
    public function add_meta_tags() {
        global $post, $wpdb;
        
        if (!is_singular() || !has_shortcode($post->post_content, 'federwiegen_product')) {
            return;
        }
        
        // Extract category from shortcode
        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';
        
        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s AND active = 1",
                $category_shortcode
            ));
        }
        
        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE active = 1 ORDER BY sort_order LIMIT 1");
        }
        
        if (!$category) {
            return;
        }
        
        // Meta title
        $meta_title = !empty($category->meta_title) ? $category->meta_title : $category->page_title;
        if (!empty($meta_title)) {
            echo '<meta name="title" content="' . esc_attr($meta_title) . '">' . "\n";
        }
        
        // Meta description
        $meta_description = !empty($category->meta_description) ? $category->meta_description : $category->page_description;
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
        
        // Keywords
        echo '<meta name="keywords" content="Federwiege mieten, Babywiege, Federwiege Verleih, Baby Schlaf, Federwiege günstig">' . "\n";
        
        // Robots
        echo '<meta name="robots" content="index, follow">' . "\n";
    }
    
    public function add_open_graph_tags() {
        global $post, $wpdb;
        
        if (!is_singular() || !has_shortcode($post->post_content, 'federwiegen_product')) {
            return;
        }
        
        // Extract category from shortcode
        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';
        
        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s AND active = 1",
                $category_shortcode
            ));
        }
        
        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE active = 1 ORDER BY sort_order LIMIT 1");
        }
        
        if (!$category) {
            return;
        }
        
        $og_title = !empty($category->meta_title) ? $category->meta_title : $category->page_title;
        $og_description = !empty($category->meta_description) ? $category->meta_description : $category->page_description;
        $og_image = !empty($category->default_image) ? $category->default_image : '';
        $og_url = get_permalink($post->ID);
        
        echo '<!-- Open Graph Tags -->' . "\n";
        echo '<meta property="og:type" content="product">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        
        if (!empty($og_image)) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }
        
        // Twitter Cards
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '">' . "\n";
        if (!empty($og_image)) {
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
        }
    }
    
    public function add_schema_markup() {
        global $post, $wpdb;
        
        if (!is_singular() || !has_shortcode($post->post_content, 'federwiegen_product')) {
            return;
        }
        
        // Extract category from shortcode
        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';
        
        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s AND active = 1",
                $category_shortcode
            ));
        }
        
        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE active = 1 ORDER BY sort_order LIMIT 1");
        }
        
        if (!$category) {
            return;
        }
        
        // Get variants for pricing
        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d AND active = 1 ORDER BY base_price",
            $category->id
        ));
        
        if (empty($variants)) {
            return;
        }
        
        $min_price = $variants[0]->base_price;
        $max_price = end($variants)->base_price;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $category->product_title,
            'description' => $category->product_description,
            'category' => 'Baby & Toddler > Baby Transport > Baby Swings',
            'brand' => array(
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            ),
            'offers' => array(
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'EUR',
                'lowPrice' => $min_price,
                'highPrice' => $max_price,
                'priceSpecification' => array(
                    '@type' => 'UnitPriceSpecification',
                    'price' => $min_price,
                    'priceCurrency' => 'EUR',
                    'unitCode' => 'MON',
                    'unitText' => 'pro Monat'
                ),
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID),
                'seller' => array(
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name')
                )
            )
        );
        
        if (!empty($category->default_image)) {
            $schema['image'] = $category->default_image;
        }
        
        // Add aggregateRating if we have analytics data
        $total_interactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_analytics WHERE category_id = %d AND event_type = 'rent_button_click'",
            $category->id
        ));
        
        if ($total_interactions > 0) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => max(1, floor($total_interactions / 10)),
                'bestRating' => '5',
                'worstRating' => '1'
            );
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n" . '</script>' . "\n";
    }
    
    // White-label functions
    private function get_branding_settings() {
        global $wpdb;
        
        $settings = array();
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
        foreach ($results as $result) {
            $settings[$result->setting_key] = $result->setting_value;
        }
        
        return $settings;
    }
    
    public function custom_admin_footer($text) {
        $branding = $this->get_branding_settings();
        
        if (isset($_GET['page']) && strpos($_GET['page'], 'federwiegen') !== false) {
            $footer_text = $branding['footer_text'] ?? 'Powered by Federwiegen Verleih Plugin';
            $company_url = $branding['company_url'] ?? '#';
            $company_name = $branding['company_name'] ?? 'Kleine Helden';
            
            return '<span id="footer-thankyou">' . $footer_text . ' | <a href="' . esc_url($company_url) . '" target="_blank">' . esc_html($company_name) . '</a></span>';
        }
        
        return $text;
    }
    
    public function custom_admin_styles() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'federwiegen') === false) {
            return;
        }
        
        $branding = $this->get_branding_settings();
        $primary_color = $branding['admin_color_primary'] ?? '#5f7f5f';
        $secondary_color = $branding['admin_color_secondary'] ?? '#4a674a';
        
        echo '<style>
            .button-primary {
                background: ' . esc_attr($primary_color) . ' !important;
                border-color: ' . esc_attr($secondary_color) . ' !important;
            }
            
            .button-primary:hover {
                background: ' . esc_attr($secondary_color) . ' !important;
            }
            
            .nav-tab-active {
                background: ' . esc_attr($primary_color) . ' !important;
                color: white !important;
                border-color: ' . esc_attr($primary_color) . ' !important;
            }
            
            .federwiegen-status-active {
                color: ' . esc_attr($secondary_color) . ' !important;
            }
        </style>';
    }
    
    public function ajax_get_product_price() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        $extra_id = intval($_POST['extra_id']);
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
            $variant_id
        ));
        
        $extra = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE id = %d",
            $extra_id
        ));
        
        $duration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_durations WHERE id = %d",
            $duration_id
        ));
        
        $condition = null;
        if ($condition_id) {
            $condition = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                $condition_id
            ));
        }
        
        // Find best matching Stripe link
        $link = $this->find_best_stripe_link($variant_id, $extra_id, $duration_id, $condition_id, $product_color_id, $frame_color_id);
        
        // Get shipping cost from category
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT shipping_cost FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d",
            $variant->category_id
        ));
        
        if ($variant && $extra && $duration) {
            $base_price = floatval($variant->base_price) + floatval($extra->price);
            
            // Apply condition price modifier
            if ($condition && $condition->price_modifier != 0) {
                $base_price = $base_price * (1 + floatval($condition->price_modifier));
            }
            
            $discount = floatval($duration->discount);
            $final_price = $base_price * (1 - $discount);
            $shipping_cost = $category ? floatval($category->shipping_cost) : 9.99;
            
            wp_send_json_success(array(
                'base_price' => $base_price,
                'final_price' => $final_price,
                'discount' => $discount,
                'shipping_cost' => $shipping_cost,
                'stripe_link' => $link ?: '#',
                'available' => $variant->available ? true : false,
                'availability_note' => $variant->availability_note ?: ''
            ));
        } else {
            wp_send_json_error('Invalid selection');
        }
    }
    
    private function find_best_stripe_link($variant_id, $extra_id, $duration_id, $condition_id = null, $product_color_id = null, $frame_color_id = null) {
        global $wpdb;
        
        // Try to find exact match first
        $exact_link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id = %d AND frame_color_id = %d",
            $variant_id, $extra_id, $duration_id, $condition_id, $product_color_id, $frame_color_id
        ));
        
        if ($exact_link) {
            return $exact_link;
        }
        
        // Try without frame color
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id = %d AND frame_color_id IS NULL",
            $variant_id, $extra_id, $duration_id, $condition_id, $product_color_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without product color
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id IS NULL AND frame_color_id = %d",
            $variant_id, $extra_id, $duration_id, $condition_id, $frame_color_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without both colors
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id IS NULL AND frame_color_id IS NULL",
            $variant_id, $extra_id, $duration_id, $condition_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without condition
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id IS NULL AND product_color_id IS NULL AND frame_color_id IS NULL",
            $variant_id, $extra_id, $duration_id
        ));
        
        return $link;
    }
    
    public function ajax_get_variant_images() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url_1, image_url_2, image_url_3, image_url_4, image_url_5 FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
            $variant_id
        ));
        
        if ($variant) {
            $images = array();
            for ($i = 1; $i <= 5; $i++) {
                $image_field = 'image_url_' . $i;
                if (!empty($variant->$image_field)) {
                    $images[] = $variant->$image_field;
                }
            }
            
            wp_send_json_success(array(
                'images' => $images
            ));
        } else {
            wp_send_json_error('Variant not found');
        }
    }
    
    public function ajax_get_extra_image() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $extra_id = intval($_POST['extra_id']);
        
        global $wpdb;
        
        $extra = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url FROM {$wpdb->prefix}federwiegen_extras WHERE id = %d",
            $extra_id
        ));
        
        if ($extra) {
            wp_send_json_success(array(
                'image_url' => $extra->image_url ?: ''
            ));
        } else {
            wp_send_json_error('Extra not found');
        }
    }
    
    public function ajax_get_variant_options() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        // Get variant-specific options
        $variant_options = $wpdb->get_results($wpdb->prepare(
            "SELECT option_type, option_id FROM {$wpdb->prefix}federwiegen_variant_options WHERE variant_id = %d",
            $variant_id
        ));
        
        $conditions = array();
        $product_colors = array();
        $frame_colors = array();
        
        if (!empty($variant_options)) {
            // Get specific options for this variant
            foreach ($variant_options as $option) {
                switch ($option->option_type) {
                    case 'condition':
                        $condition = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d AND active = 1 AND available = 1",
                            $option->option_id
                        ));
                        if ($condition) {
                            $conditions[] = $condition;
                        }
                        break;
                    case 'product_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d AND active = 1 AND available = 1",
                            $option->option_id
                        ));
                        if ($color) {
                            $product_colors[] = $color;
                        }
                        break;
                    case 'frame_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d AND active = 1 AND available = 1",
                            $option->option_id
                        ));
                        if ($color) {
                            $frame_colors[] = $color;
                        }
                        break;
                }
            }
        } else {
            // No specific options defined, get all available options for the category
            $variant = $wpdb->get_row($wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
                $variant_id
            ));
            
            if ($variant) {
                $conditions = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d AND active = 1 AND available = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
                
                $product_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' AND active = 1 AND available = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
                
                $frame_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' AND active = 1 AND available = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
            }
        }
        
        wp_send_json_success(array(
            'conditions' => $conditions,
            'product_colors' => $product_colors,
            'frame_colors' => $frame_colors
        ));
    }
    
    public function ajax_submit_order() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $variant_id = intval($_POST['variant_id']);
        $extra_id = intval($_POST['extra_id']);
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        $final_price = floatval($_POST['final_price']);
        $stripe_link = esc_url_raw($_POST['stripe_link']);
        
        global $wpdb;
        
        // Insert order
        $result = $wpdb->insert(
            $wpdb->prefix . 'federwiegen_orders',
            array(
                'category_id' => $category_id,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'final_price' => $final_price,
                'stripe_link' => $stripe_link,
                'customer_name' => '',
                'customer_email' => '',
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array('order_id' => $wpdb->insert_id));
        } else {
            wp_send_json_error('Failed to save order');
        }
    }
    
    public function ajax_track_interaction() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $event_type = sanitize_text_field($_POST['event_type']);
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
        $extra_id = isset($_POST['extra_id']) ? intval($_POST['extra_id']) : null;
        $duration_id = isset($_POST['duration_id']) ? intval($_POST['duration_id']) : null;
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'federwiegen_analytics',
            array(
                'category_id' => $category_id,
                'event_type' => $event_type,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }
    
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Admin page methods
    public function admin_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/main-page.php';
    }
    
    public function categories_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/categories-page.php';
    }
    
    public function variants_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/variants-page.php';
    }
    
    public function extras_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/extras-page.php';
    }
    
    public function durations_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/durations-page.php';
    }
    
    public function conditions_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/conditions-page.php';
    }
    
    public function colors_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/colors-page.php';
    }
    
    public function variant_options_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/variant-options-page.php';
    }
    
    public function links_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/links-page.php';
    }
    
    public function orders_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/orders-page.php';
    }
    
    public function analytics_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/analytics-page.php';
    }
    
    public function branding_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/branding-page.php';
    }
    
    public function debug_page() {
        include FEDERWIEGEN_PLUGIN_PATH . 'admin/debug-page.php';
    }
}
