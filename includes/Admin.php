<?php
namespace FederwiegenVerleih;

class Admin {
    public function add_admin_menu() {
        $branding = $this->get_branding_settings();
        $menu_title = $branding['plugin_name'] ?? 'Federwiegen';
        
        add_menu_page(
            $branding['plugin_name'] ?? 'Rent Plugin',
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
            $footer_text = $branding['footer_text'] ?? 'Powered by H2 Concepts';
            $company_url = $branding['company_url'] ?? '#';
            $company_name = $branding['company_name'] ?? 'H2 Concepts';
            
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
                background: ' . esc_attr($primary_color) . ';
                color: #fff;
                border-color: ' . esc_attr($secondary_color) . ';
            }
        </style>';
       }

    /**
     * Verify nonce and user capabilities for admin form submissions.
     */
    public static function verify_admin_action($nonce_field = 'federwiegen_admin_nonce') {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'h2-concepts'));
        }
        check_admin_referer('federwiegen_admin_action', $nonce_field);
    }
    
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
