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
        // Run database update check as early as possible
        $this->check_for_updates();
        add_action('wp_head', [$this, 'add_meta_tags']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        add_action('wp_head', [$this, 'add_open_graph_tags']);
    }

    public function init() {
        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_shortcode('federwiegen_product', [$this, 'product_shortcode']);
        add_shortcode('federwiegen_categories', [$this, 'categories_shortcode']);
        add_action('wp_enqueue_scripts', [$this->admin, 'enqueue_frontend_assets']);
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
        add_action('wp_ajax_notify_availability', [$this->ajax, 'ajax_notify_availability']);
        add_action('wp_ajax_nopriv_notify_availability', [$this->ajax, 'ajax_notify_availability']);

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

    public function product_shortcode($atts) {
        global $wpdb;

        $atts = shortcode_atts([
            'category' => '',
            'title' => '',
            'description' => ''
        ], $atts);

        $category = null;
        if (!empty($atts['category'])) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s OR name = %s",
                $atts['category'],
                $atts['category']
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return '<p>Keine aktive Produktkategorie gefunden.</p>';
        }

        $page_title = !empty($atts['title']) ? $atts['title'] : $category->page_title;
        $page_description = !empty($atts['description']) ? $atts['description'] : $category->page_description;

        ob_start();
        include FEDERWIEGEN_PLUGIN_PATH . 'templates/product-page.php';
        return ob_get_clean();
    }

    /**
     * Shortcode to list all active product categories.
     * Usage: [federwiegen_categories]
     */
    public function categories_shortcode($atts) {
        ob_start();
        include FEDERWIEGEN_PLUGIN_PATH . 'templates/category-list.php';
        return ob_get_clean();
    }

    public function add_meta_tags() {
        global $post, $wpdb;

        if (!is_singular() || !has_shortcode($post->post_content, 'federwiegen_product')) {
            return;
        }

        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return;
        }

        $meta_title = !empty($category->meta_title) ? $category->meta_title : $category->page_title;
        if (!empty($meta_title)) {
            echo '<meta name="title" content="' . esc_attr($meta_title) . '">' . "\n";
        }

        $meta_description = !empty($category->meta_description) ? $category->meta_description : $category->page_description;
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }

        echo '<meta name="keywords" content="Federwiege mieten, Babywiege, Federwiege Verleih, Baby Schlaf, Federwiege günstig">' . "\n";
        echo '<meta name="robots" content="index, follow">' . "\n";
    }

    public function add_open_graph_tags() {
        global $post, $wpdb;

        if (!is_singular() || !has_shortcode($post->post_content, 'federwiegen_product')) {
            return;
        }

        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order LIMIT 1");
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

        $pattern = '/\[federwiegen_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return;
        }

        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d ORDER BY base_price",
            $category->id
        ));

        if (empty($variants)) {
            return;
        }

        $min_price = $variants[0]->base_price;
        $max_price = end($variants)->base_price;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $category->product_title,
            'description' => $category->product_description,
            'category' => 'Baby & Toddler > Baby Transport > Baby Swings',
            'brand' => [
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'EUR',
                'lowPrice' => $min_price,
                'highPrice' => $max_price,
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'price' => $min_price,
                    'priceCurrency' => 'EUR',
                    'unitCode' => 'MON',
                    'unitText' => 'pro Monat'
                ],
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID),
                'seller' => [
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name')
                ]
            ]
        ];

        if (!empty($category->default_image)) {
            $schema['image'] = $category->default_image;
        }

        $total_interactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_analytics WHERE category_id = %d AND event_type = 'rent_button_click'",
            $category->id
        ));

        if ($total_interactions > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => max(1, floor($total_interactions / 10)),
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }

        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n" . '</script>' . "\n";
    }
}
