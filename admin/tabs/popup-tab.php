<?php
// Popup Tab Content

if (isset($_POST['submit_popup'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $settings = [
        'title'   => sanitize_text_field($_POST['popup_title'] ?? ''),
        'content' => wp_kses_post($_POST['popup_content'] ?? ''),
        'options' => sanitize_textarea_field($_POST['popup_options'] ?? '')
    ];
    update_option('federwiegen_popup_settings', $settings);
    echo '<div class="notice notice-success"><p>✅ Popup-Einstellungen gespeichert!</p></div>';
}

$popup_settings = get_option('federwiegen_popup_settings', []);
$popup_title   = $popup_settings['title'] ?? '';
$popup_content = $popup_settings['content'] ?? '';
$popup_options = $popup_settings['options'] ?? '';
?>

<div class="federwiegen-branding-tab">
    <form method="post" action="">
        <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
        <div class="federwiegen-form-section">
            <h4>📣 Popup Inhalt</h4>
            <div class="federwiegen-form-grid">
                <div class="federwiegen-form-group">
                    <label>Titel</label>
                    <input type="text" name="popup_title" value="<?php echo esc_attr($popup_title); ?>">
                </div>
                <div class="federwiegen-form-group full-width">
                    <label>Text</label>
                    <?php wp_editor($popup_content, 'popup_content', ['textarea_name' => 'popup_content']); ?>
                </div>
                <div class="federwiegen-form-group full-width">
                    <label>Auswahloptionen (optional, eine pro Zeile)</label>
                    <textarea name="popup_options" rows="4" placeholder="Option 1\nOption 2\nOption 3"><?php echo esc_textarea($popup_options); ?></textarea>
                </div>
            </div>
        </div>
        <?php submit_button('💾 Einstellungen speichern', 'primary', 'submit_popup'); ?>
    </form>
</div>
