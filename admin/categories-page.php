<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Handle form submissions for categories
if (isset($_POST['submit_category'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['name']);
    $shortcode = sanitize_text_field($_POST['shortcode']);
    $page_title = sanitize_text_field($_POST['page_title']);
    $page_description = sanitize_textarea_field($_POST['page_description']);
    $meta_title = sanitize_text_field($_POST['meta_title']);
    $meta_description = sanitize_textarea_field($_POST['meta_description']);
    $product_title = sanitize_text_field($_POST['product_title']);
    $product_description = sanitize_textarea_field($_POST['product_description']);
    $default_image = esc_url_raw($_POST['default_image']);
    
    // Features
    $features_title = sanitize_text_field($_POST['features_title']);
    $feature_1_icon = esc_url_raw($_POST['feature_1_icon']);
    $feature_1_title = sanitize_text_field($_POST['feature_1_title']);
    $feature_1_description = sanitize_textarea_field($_POST['feature_1_description']);
    $feature_2_icon = esc_url_raw($_POST['feature_2_icon']);
    $feature_2_title = sanitize_text_field($_POST['feature_2_title']);
    $feature_2_description = sanitize_textarea_field($_POST['feature_2_description']);
    $feature_3_icon = esc_url_raw($_POST['feature_3_icon']);
    $feature_3_title = sanitize_text_field($_POST['feature_3_title']);
    $feature_3_description = sanitize_textarea_field($_POST['feature_3_description']);
    
    // Button
    $button_text = sanitize_text_field($_POST['button_text']);
    $button_icon = esc_url_raw($_POST['button_icon']);
    
    // Shipping
    $shipping_cost = floatval($_POST['shipping_cost']);
    
    // Layout
    $layout_style = sanitize_text_field($_POST['layout_style']);
    
    // Tooltips
    $duration_tooltip = sanitize_textarea_field($_POST['duration_tooltip']);
    $condition_tooltip = sanitize_textarea_field($_POST['condition_tooltip']);
    
    $active = isset($_POST['active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order']);

    $table_name = $wpdb->prefix . 'federwiegen_categories';

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'shortcode' => $shortcode,
                'page_title' => $page_title,
                'page_description' => $page_description,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'product_title' => $product_title,
                'product_description' => $product_description,
                'default_image' => $default_image,
                'features_title' => $features_title,
                'feature_1_icon' => $feature_1_icon,
                'feature_1_title' => $feature_1_title,
                'feature_1_description' => $feature_1_description,
                'feature_2_icon' => $feature_2_icon,
                'feature_2_title' => $feature_2_title,
                'feature_2_description' => $feature_2_description,
                'feature_3_icon' => $feature_3_icon,
                'feature_3_title' => $feature_3_title,
                'feature_3_description' => $feature_3_description,
                'button_text' => $button_text,
                'button_icon' => $button_icon,
                'shipping_cost' => $shipping_cost,
                'layout_style' => $layout_style,
                'duration_tooltip' => $duration_tooltip,
                'condition_tooltip' => $condition_tooltip,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Kategorie erfolgreich aktualisiert!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'shortcode' => $shortcode,
                'page_title' => $page_title,
                'page_description' => $page_description,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'product_title' => $product_title,
                'product_description' => $product_description,
                'default_image' => $default_image,
                'features_title' => $features_title,
                'feature_1_icon' => $feature_1_icon,
                'feature_1_title' => $feature_1_title,
                'feature_1_description' => $feature_1_description,
                'feature_2_icon' => $feature_2_icon,
                'feature_2_title' => $feature_2_title,
                'feature_2_description' => $feature_2_description,
                'feature_3_icon' => $feature_3_icon,
                'feature_3_title' => $feature_3_title,
                'feature_3_description' => $feature_3_description,
                'button_text' => $button_text,
                'button_icon' => $button_icon,
                'shipping_cost' => $shipping_cost,
                'layout_style' => $layout_style,
                'duration_tooltip' => $duration_tooltip,
                'condition_tooltip' => $condition_tooltip,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Kategorie erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    $table_name = $wpdb->prefix . 'federwiegen_categories';
    
    // Check if this is the last category
    $category_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE active = 1");
    if ($category_count <= 1) {
        echo '<div class="notice notice-error"><p>❌ Sie können nicht die letzte aktive Kategorie löschen!</p></div>';
    } else {
        $result = $wpdb->delete($table_name, array('id' => $category_id), array('%d'));
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Kategorie gelöscht!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", intval($_GET['edit'])));
}

// Get all categories
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");

// Get branding settings
$branding = array();
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🏷️</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Kategorien verwalten</h1>
            <p>Produktkategorien & SEO-Einstellungen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Kategorien</strong>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neue Kategorie
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=edit&edit=' . $edit_item->id); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'edit' ? 'active' : ''; ?>">
            ✏️ Bearbeiten
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Tab Content -->
    <div class="federwiegen-tab-content">
        <?php
        switch ($active_tab) {
            case 'add':
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-edit-tab.php';
                } else {
                    include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-list-tab.php';
                }
                break;
            case 'list':
            default:
                include FEDERWIEGEN_PLUGIN_PATH . 'admin/tabs/categories-list-tab.php';
        }
        ?>
    </div>
</div>
