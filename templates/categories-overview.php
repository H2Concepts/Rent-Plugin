<?php
/**
 * Template for categories overview shortcode
 */
?>
<div class="federwiegen-categories-overview">
    <?php foreach ($categories as $cat): ?>
        <?php
            $page = get_page_by_path($cat->shortcode);
            $link = $page ? get_permalink($page) : '#';
            $min_price = $wpdb->get_var($wpdb->prepare("SELECT MIN(base_price) FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d", $cat->id));
        ?>
        <div class="federwiegen-category-box">
            <?php if (!empty($cat->default_image)): ?>
                <a href="<?php echo esc_url($link); ?>" class="federwiegen-category-image">
                    <img src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->name); ?>">
                </a>
            <?php endif; ?>
            <h3 class="federwiegen-category-title"><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($cat->name); ?></a></h3>
            <?php if (!empty($cat->short_description)): ?>
                <p class="federwiegen-category-desc"><?php echo esc_html($cat->short_description); ?></p>
            <?php endif; ?>
            <?php if ($min_price !== null): ?>
                <div class="federwiegen-category-price">ab <?php echo number_format((float)$min_price, 2, ',', '.'); ?>€</div>
            <?php endif; ?>
            <a href="<?php echo esc_url($link); ?>" class="federwiegen-category-button">Produkt ansehen</a>
        </div>
    <?php endforeach; ?>
</div>
