<?php
// Categories Edit Tab Content
?>

<div class="federwiegen-edit-category">
    <div class="federwiegen-form-header">
        <h3>✏️ Kategorie bearbeiten</h3>
        <p>Bearbeiten Sie die Kategorie "<?php echo esc_html($edit_item->name); ?>" mit allen Einstellungen und Inhalten.</p>
    </div>
    
    <form method="post" action="" class="federwiegen-compact-form">
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        
        <!-- Grunddaten -->
        <div class="federwiegen-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Kategorie-Name *</label>
                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                </div>
                <div class="federwiegen-form-group">
                    <label>Shortcode-Bezeichnung *</label>
                    <input type="text" name="shortcode" value="<?php echo esc_attr($edit_item->shortcode); ?>" required pattern="[a-z0-9_-]+">
                    <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                </div>
            </div>
        </div>
        
        <!-- SEO-Einstellungen -->
        <div class="federwiegen-form-section">
            <h4>🔍 SEO-Einstellungen</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>SEO-Titel</label>
                    <input type="text" name="meta_title" value="<?php echo esc_attr($edit_item->meta_title ?? ''); ?>" maxlength="60">
                    <small>Max. 60 Zeichen für Google</small>
                </div>
                <div class="federwiegen-form-group">
                    <label>Layout-Stil</label>
                    <select name="layout_style">
                        <option value="default" <?php selected($edit_item->layout_style ?? 'default', 'default'); ?>>Standard (Horizontal)</option>
                        <option value="grid" <?php selected($edit_item->layout_style ?? 'default', 'grid'); ?>>Grid (Karten-Layout)</option>
                        <option value="list" <?php selected($edit_item->layout_style ?? 'default', 'list'); ?>>Liste (Vertikal)</option>
                    </select>
                </div>
            </div>
            
            <div class="federwiegen-form-group">
                <label>SEO-Beschreibung</label>
                <textarea name="meta_description" rows="3" maxlength="160"><?php echo esc_textarea($edit_item->meta_description ?? ''); ?></textarea>
            </div>
        </div>
        
        <!-- Seiteninhalte -->
        <div class="federwiegen-form-section">
            <h4>📄 Seiteninhalte</h4>
            <div class="federwiegen-form-group">
                <label>Haupttitel der Seite *</label>
                <input type="text" name="page_title" value="<?php echo esc_attr($edit_item->page_title); ?>" required>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Hauptbeschreibung der Seite *</label>
                <textarea name="page_description" rows="3" required><?php echo esc_textarea($edit_item->page_description); ?></textarea>
            </div>
            
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Produkttitel *</label>
                    <input type="text" name="product_title" value="<?php echo esc_attr($edit_item->product_title); ?>" required>
                </div>
                <div class="federwiegen-form-group">
                    <label>Versandkosten (€)</label>
                    <input type="number" name="shipping_cost" value="<?php echo esc_attr($edit_item->shipping_cost); ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Produktbeschreibung *</label>
                <textarea name="product_description" rows="4" required><?php echo esc_textarea($edit_item->product_description); ?></textarea>
            </div>
        </div>
        
        <!-- Bilder -->
        <div class="federwiegen-form-section">
            <h4>📸 Standard-Produktbild</h4>
            <div class="federwiegen-form-group">
                <label>Standard-Produktbild</label>
                <div class="federwiegen-upload-area">
                    <input type="url" name="default_image" id="default_image" value="<?php echo esc_attr($edit_item->default_image); ?>">
                    <button type="button" class="button federwiegen-media-button" data-target="default_image">📁 Aus Mediathek wählen</button>
                </div>
                <small>Fallback-Bild wenn für Ausführungen kein spezifisches Bild hinterlegt ist</small>
                
                <?php if (!empty($edit_item->default_image)): ?>
                <div class="federwiegen-image-preview">
                    <img src="<?php echo esc_url($edit_item->default_image); ?>" alt="Standard-Produktbild">
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Features -->
        <div class="federwiegen-form-section">
            <h4>🌟 Features-Sektion</h4>
            <div class="federwiegen-form-group">
                <label>Features-Überschrift</label>
                <input type="text" name="features_title" value="<?php echo esc_attr($edit_item->features_title); ?>">
            </div>
            
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="federwiegen-feature-group">
                <h5>Feature <?php echo $i; ?></h5>
                <div class="federwiegen-form-row">
                    <div class="federwiegen-form-group">
                        <label>Titel</label>
                        <input type="text" name="feature_<?php echo $i; ?>_title" value="<?php echo esc_attr($edit_item->{'feature_' . $i . '_title'}); ?>">
                    </div>
                    <div class="federwiegen-form-group">
                        <label>Icon-Bild</label>
                        <div class="federwiegen-upload-area">
                            <input type="url" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" value="<?php echo esc_attr($edit_item->{'feature_' . $i . '_icon'}); ?>">
                            <button type="button" class="button federwiegen-media-button" data-target="feature_<?php echo $i; ?>_icon">📁</button>
                        </div>
                        <?php if (!empty($edit_item->{'feature_' . $i . '_icon'})): ?>
                        <div class="federwiegen-icon-preview">
                            <img src="<?php echo esc_url($edit_item->{'feature_' . $i . '_icon'}); ?>" alt="Feature <?php echo $i; ?> Icon">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="federwiegen-form-group">
                    <label>Beschreibung</label>
@@ -140,56 +140,56 @@
        
        <!-- Button & Tooltips -->
        <div class="federwiegen-form-section">
            <h4>🔘 Button & Tooltips</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Button-Text</label>
                    <input type="text" name="button_text" value="<?php echo esc_attr($edit_item->button_text); ?>">
                </div>
                <div class="federwiegen-form-group">
                    <label>Button-Icon</label>
                    <div class="federwiegen-upload-area">
                        <input type="url" name="button_icon" id="button_icon" value="<?php echo esc_attr($edit_item->button_icon); ?>">
                        <button type="button" class="button federwiegen-media-button" data-target="button_icon">📁</button>
                    </div>
                    <?php if (!empty($edit_item->button_icon)): ?>
                    <div class="federwiegen-icon-preview">
                        <img src="<?php echo esc_url($edit_item->button_icon); ?>" alt="Button Icon">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Mietdauer-Tooltip</label>
                <textarea name="duration_tooltip" rows="3"><?php echo esc_textarea($edit_item->duration_tooltip); ?></textarea>
            </div>
            
            <div class="federwiegen-form-group">
                <label>Zustand-Tooltip</label>
                <textarea name="condition_tooltip" rows="4"><?php echo esc_textarea($edit_item->condition_tooltip); ?></textarea>
            </div>
        </div>
        
        <!-- Einstellungen -->
        <div class="federwiegen-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="federwiegen-form-row">
                <div class="federwiegen-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                </div>
                <div class="federwiegen-form-group">
                    <label class="federwiegen-checkbox-label">
                        <input type="checkbox" name="active" value="1" <?php echo $edit_item->active ? 'checked' : ''; ?>>
                        <span>Aktiv</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="federwiegen-form-actions">
            <button type="submit" name="submit_category" class="button button-primary button-large">
                ✅ Änderungen speichern
            </button>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-categories&delete=' . $edit_item->id); ?>" 
               class="button button-large federwiegen-delete-button" 
               onclick="return confirm('Sind Sie sicher, dass Sie diese Kategorie löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" und alle zugehörigen Daten werden unwiderruflich gelöscht!')"
               style="margin-left: auto;">
                🗑️ Löschen
            </a>
        </div>
    </form>
</div>

<style>
.federwiegen-image-preview img {
    width: 100%;
    max-width: 200px;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 10px;
}

.federwiegen-icon-preview img {
    width: 32px;
    height: 32px;
    object-fit: contain;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // WordPress Media Library Integration
    document.querySelectorAll('.federwiegen-media-button').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            
            if (!targetInput) return;
            
            const mediaUploader = wp.media({
                title: 'Bild auswählen',
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.value = attachment.url;
            });
            
            mediaUploader.open();
        });
    });
});
</script>