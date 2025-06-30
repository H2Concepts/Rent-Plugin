<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Force database update if requested
if (isset($_POST['force_update'])) {
    $table_variants = $wpdb->prefix . 'federwiegen_variants';
    
    // Check if image_url column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'image_url'");
    
    if (empty($column_exists)) {
        $result = $wpdb->query("ALTER TABLE $table_variants ADD COLUMN image_url TEXT AFTER base_price");
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ image_url Spalte erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen der image_url Spalte: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-info"><p>ℹ️ image_url Spalte existiert bereits.</p></div>';
    }
    
    // Create settings table if it doesn't exist
    $table_settings = $wpdb->prefix . 'federwiegen_settings';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_settings'");
    
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        echo '<div class="notice notice-success"><p>✅ Settings Tabelle erfolgreich erstellt!</p></div>';
    } else {
        echo '<div class="notice notice-info"><p>ℹ️ Settings Tabelle existiert bereits.</p></div>';
    }
}

// Get table structure
$table_variants = $wpdb->prefix . 'federwiegen_variants';
$table_settings = $wpdb->prefix . 'federwiegen_settings';

$variants_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_variants");
$settings_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_settings'");

if ($settings_exists) {
    $settings_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_settings");
} else {
    $settings_columns = array();
}

// Get sample data
$sample_variant = $wpdb->get_row("SELECT * FROM $table_variants LIMIT 1");
$sample_settings = $wpdb->get_results("SELECT * FROM $table_settings LIMIT 5");
?>

<div class="wrap">
    <h1>🔧 Federwiegen Debug</h1>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h3>⚠️ Nur für Fehlerbehebung verwenden!</h3>
        <p>Diese Seite hilft bei der Diagnose von Datenbankproblemen.</p>
    </div>
    
    <form method="post" action="">
        <p>
            <button type="submit" name="force_update" class="button button-primary" onclick="return confirm('Sind Sie sicher? Dies führt Datenbankänderungen durch.')">
                🔄 Datenbank reparieren
            </button>
        </p>
    </form>
    
    <h2>📊 Datenbankstatus</h2>
    
    <h3>Variants Tabelle (<?php echo $table_variants; ?>)</h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Spaltenname</th>
                <th>Typ</th>
                <th>Null</th>
                <th>Standard</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($variants_columns as $column): ?>
            <tr>
                <td><strong><?php echo $column->Field; ?></strong></td>
                <td><?php echo $column->Type; ?></td>
                <td><?php echo $column->Null; ?></td>
                <td><?php echo $column->Default; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if ($sample_variant): ?>
    <h4>Beispiel-Datensatz:</h4>
    <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto;">
<?php print_r($sample_variant); ?>
    </pre>
    <?php endif; ?>
    
    <h3>Settings Tabelle (<?php echo $table_settings; ?>)</h3>
    <?php if ($settings_exists): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Spaltenname</th>
                <th>Typ</th>
                <th>Null</th>
                <th>Standard</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($settings_columns as $column): ?>
            <tr>
                <td><strong><?php echo $column->Field; ?></strong></td>
                <td><?php echo $column->Type; ?></td>
                <td><?php echo $column->Null; ?></td>
                <td><?php echo $column->Default; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (!empty($sample_settings)): ?>
    <h4>Beispiel-Einstellungen:</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Schlüssel</th>
                <th>Wert</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sample_settings as $setting): ?>
            <tr>
                <td><strong><?php echo esc_html($setting->setting_key); ?></strong></td>
                <td><?php echo esc_html(substr($setting->setting_value, 0, 100)) . (strlen($setting->setting_value) > 100 ? '...' : ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    
    <?php else: ?>
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
        <strong>❌ Settings Tabelle existiert nicht!</strong>
        <p>Klicken Sie auf "Datenbank reparieren" um sie zu erstellen.</p>
    </div>
    <?php endif; ?>
    
    <h2>🔍 Systeminfo</h2>
    <ul>
        <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
        <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
        <li><strong>MySQL Version:</strong> <?php echo $wpdb->db_version(); ?></li>
        <li><strong>Plugin Version:</strong> <?php echo FEDERWIEGEN_VERSION; ?></li>
        <li><strong>Gespeicherte Version:</strong> <?php echo get_option('federwiegen_version', 'nicht gesetzt'); ?></li>
    </ul>
</div>
