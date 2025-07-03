<?php
if (!defined('ABSPATH')) {
    exit;
}

// Query categories
global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE active = 1 ORDER BY sort_order");
?>
<div class="federwiegen-categories-wrapper">
    <?php if (empty($categories)) : ?>
        <p><?php esc_html_e('Keine Kategorien gefunden.', 'h2-concepts'); ?></p>
    <?php else : ?>
        <div class="federwiegen-categories-grid">
            <?php foreach ($categories as $cat) : ?>
                <div class="federwiegen-category-item">
                    <div class="federwiegen-category-thumb">
                        <?php if (!empty($cat->default_image)) : ?>
                            <img src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->name); ?>">
                        <?php else : ?>
                            <span class="federwiegen-category-placeholder">🏷️</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="federwiegen-category-title"><?php echo esc_html($cat->page_title ?: $cat->name); ?></h3>
                    <?php if (!empty($cat->page_description)) : ?>
                        <p class="federwiegen-category-desc"><?php echo esc_html($cat->page_description); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
