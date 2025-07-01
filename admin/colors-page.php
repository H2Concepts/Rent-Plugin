<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'federwiegen_colors';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE active = 1 ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Handle form submissions
if (isset($_POST['submit'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $color_code = sanitize_hex_color($_POST['color_code']);
    $color_type = sanitize_text_field($_POST['color_type']);
    $available = isset($_POST['available']) ? 1 : 0;
    $active = isset($_POST['active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'color_code' => $color_code,
                'color_type' => $color_type,
                'available' => $available,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%s', '%s', '%d', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Farbe erfolgreich aktualisiert!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'color_code' => $color_code,
                'color_type' => $color_type,
                'available' => $available,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Farbe erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Farbe gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $selected_category = $edit_item->category_id;
    }
}

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", $selected_category));

// Get all colors for selected category, separated by type
$product_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order, name", $selected_category));
$frame_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order, name", $selected_category));
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🎨</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Farben verwalten</h1>
            <p>Produkt- & Gestellfarben</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Farben</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="federwiegen-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="federwiegen-colors">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <label for="category-select"><strong>🏷️ Kategorie:</strong></label>
            <select name="category" id="category-select" onchange="this.form.submit()">
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <noscript><input type="submit" value="Wechseln" class="button"></noscript>
        </form>
        
        <?php if ($current_category): ?>
        <div class="federwiegen-category-info">
            <code>[federwiegen_product category="<?php echo esc_html($current_category->shortcode); ?>"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neue Farbe
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
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
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🎨 Neue Farbe hinzufügen</h3>
                    <p>Erstellen Sie eine neue Produkt- oder Gestellfarbe.</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Farbtyp *</label>
                                    <select name="color_type" required>
                                        <option value="product">🎨 Produktfarbe</option>
                                        <option value="frame">🖼️ Gestellfarbe</option>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Farbname *</label>
                                    <input type="text" name="name" required>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Farbcode *</label>
                                    <input type="color" name="color_code" value="#FFFFFF" required>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Sortierung</label>
                                    <input type="number" name="sort_order" value="0" min="0">
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>
                                        <input type="checkbox" name="available" value="1" checked>
                                        Verfügbar
                                    </label>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>
                                        <input type="checkbox" name="active" value="1" checked>
                                        Aktiv
                                    </label>
                                </div>
                            </div>
                            
                            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Hinzufügen', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                break;
                
            case 'edit':
                if ($edit_item):
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🎨 Farbe bearbeiten</h3>
                    <p>Bearbeiten Sie die Eigenschaften der Farbe.</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                            
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Farbtyp *</label>
                                    <select name="color_type" required>
                                        <option value="product" <?php selected($edit_item->color_type, 'product'); ?>>🎨 Produktfarbe</option>
                                        <option value="frame" <?php selected($edit_item->color_type, 'frame'); ?>>🖼️ Gestellfarbe</option>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Farbname *</label>
                                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Farbcode *</label>
                                    <input type="color" name="color_code" value="<?php echo esc_attr($edit_item->color_code); ?>" required>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Sortierung</label>
                                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>
                                        <input type="checkbox" name="available" value="1" <?php checked($edit_item->available); ?>>
                                        Verfügbar
                                    </label>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>
                                        <input type="checkbox" name="active" value="1" <?php checked($edit_item->active); ?>>
                                        Aktiv
                                    </label>
                                </div>
                            </div>
                            
                            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Aktualisieren', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                else:
                    echo '<div class="federwiegen-tab-section"><p>Farbe nicht gefunden.</p></div>';
                endif;
                break;
                
            case 'list':
            default:
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🎨 Farben</h3>
                    <p>Verwalten Sie Produkt- und Gestellfarben für Ihre Federwiegen.</p>
                    
                    <!-- Product Colors -->
                    <div class="federwiegen-list-card" style="margin-bottom: 30px;">
                        <h4>🎨 Produktfarben</h4>
                        
                        <?php if (empty($product_colors)): ?>
                        <div class="federwiegen-empty-state">
                            <p>Noch keine Produktfarben vorhanden.</p>
                        </div>
                        <?php else: ?>
                        
                        <div class="federwiegen-items-grid">
                            <?php foreach ($product_colors as $color): ?>
                            <div class="federwiegen-item-card">
                                <div class="federwiegen-item-content">
                                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid #ddd; background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                                        <div>
                                            <h5 style="margin: 0;"><?php echo esc_html($color->name); ?></h5>
                                            <code style="font-size: 12px;"><?php echo esc_html($color->color_code); ?></code>
                                        </div>
                                    </div>
                                    <div class="federwiegen-item-meta">
                                        <span class="federwiegen-status <?php echo $color->available ? 'available' : 'unavailable'; ?>">
                                            <?php echo $color->available ? '✅ Verfügbar' : '❌ Nicht verfügbar'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="federwiegen-item-actions">
                                    <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=edit&edit=' . $color->id); ?>" class="button button-small">Bearbeiten</a>
                                    <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=list&delete=' . $color->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                    
                    <!-- Frame Colors -->
                    <div class="federwiegen-list-card">
                        <h4>🖼️ Gestellfarben</h4>
                        
                        <?php if (empty($frame_colors)): ?>
                        <div class="federwiegen-empty-state">
                            <p>Noch keine Gestellfarben vorhanden.</p>
                        </div>
                        <?php else: ?>
                        
                        <div class="federwiegen-items-grid">
                            <?php foreach ($frame_colors as $color): ?>
                            <div class="federwiegen-item-card">
                                <div class="federwiegen-item-content">
                                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid #ddd; background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                                        <div>
                                            <h5 style="margin: 0;"><?php echo esc_html($color->name); ?></h5>
                                            <code style="font-size: 12px;"><?php echo esc_html($color->color_code); ?></code>
                                        </div>
                                    </div>
                                    <div class="federwiegen-item-meta">
                                        <span class="federwiegen-status <?php echo $color->available ? 'available' : 'unavailable'; ?>">
                                            <?php echo $color->available ? '✅ Verfügbar' : '❌ Nicht verfügbar'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="federwiegen-item-actions">
                                    <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=edit&edit=' . $color->id); ?>" class="button button-small">Bearbeiten</a>
                                    <a href="<?php echo admin_url('admin.php?page=federwiegen-colors&category=' . $selected_category . '&tab=list&delete=' . $color->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
                <?php
        }
        ?>
    </div>
</div>
