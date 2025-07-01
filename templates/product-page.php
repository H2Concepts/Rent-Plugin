<?php

global $wpdb;

// Get category data
$category_id = isset($category) ? $category->id : 1;

// Get all data for this category
$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$extras = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$durations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_durations WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

// Get category settings
$default_image = isset($category) ? $category->default_image : '';
$product_title = isset($category) ? $category->product_title : '';
$product_description = isset($category) ? $category->product_description : '';

// Features
$features_title = isset($category) ? ($category->features_title ?? '') : '';
$feature_1_icon = isset($category) ? $category->feature_1_icon : '';
$feature_1_title = isset($category) ? $category->feature_1_title : '';
$feature_1_description = isset($category) ? $category->feature_1_description : '';
$feature_2_icon = isset($category) ? $category->feature_2_icon : '';
$feature_2_title = isset($category) ? $category->feature_2_title : '';
$feature_2_description = isset($category) ? $category->feature_2_description : '';
$feature_3_icon = isset($category) ? $category->feature_3_icon : '';
$feature_3_title = isset($category) ? $category->feature_3_title : '';
$feature_3_description = isset($category) ? $category->feature_3_description : '';

// Button
$button_text = isset($category) ? $category->button_text : '';
$button_icon = isset($category) ? $category->button_icon : '';
$payment_icons = [];
if (isset($category) && property_exists($category, 'payment_icons')) {
    $payment_icons = array_filter(array_map('trim', explode(',', $category->payment_icons)));
}

// Shipping
$shipping_cost = isset($category) ? ($category->shipping_cost ?? 0) : 0;

// Layout
$layout_style = isset($category) ? ($category->layout_style ?? 'default') : 'default';

// Tooltips
$duration_tooltip = isset($category) ? ($category->duration_tooltip ?? '') : '';
$condition_tooltip = isset($category) ? ($category->condition_tooltip ?? '') : '';

// Get initial conditions and colors (will be updated via AJAX when variant is selected)
$initial_conditions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$initial_product_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order",
    $category_id
));

$initial_frame_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order",
    $category_id
));
?>

<div class="federwiegen-container" data-category-id="<?php echo esc_attr($category_id); ?>" data-layout="<?php echo esc_attr($layout_style); ?>">

    <div class="federwiegen-content">
        <div class="federwiegen-left">
            <div class="federwiegen-product-info">
                <div class="federwiegen-product-image">
                    <div class="federwiegen-image-gallery" id="federwiegen-image-gallery">
                        <!-- Main Image Container -->
                        <div class="federwiegen-main-image-container" id="federwiegen-main-image-container">
                            <?php if (!empty($default_image)): ?>
                                <img src="<?php echo esc_url($default_image); ?>" alt="Federwiege" id="federwiegen-main-image" class="federwiegen-main-image">
                            <?php else: ?>
                                <div class="federwiegen-placeholder-image" id="federwiegen-placeholder">👶</div>
                            <?php endif; ?>
                            
                            <!-- Extra Image Overlay -->
                            <div class="federwiegen-extra-overlay" id="federwiegen-extra-overlay" style="display: none;">
                                <img src="" alt="Extra" id="federwiegen-extra-image" class="federwiegen-extra-image">
                            </div>
                        </div>
                        
                        <!-- Thumbnail Navigation -->
                        <div class="federwiegen-thumbnails" id="federwiegen-thumbnails" style="display: none;">
                            <!-- Thumbnails will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <div class="federwiegen-product-details">
                    <h2><?php echo esc_html($product_title); ?></h2>
                    <p><?php echo esc_html($product_description); ?></p>
                    
                    <div class="federwiegen-features">
                        <div class="federwiegen-feature">
                            <span class="federwiegen-feature-icon">🛡️</span>
                            <span>Sicherheitsgeprüft</span>
                        </div>
                        <div class="federwiegen-feature">
                            <span class="federwiegen-feature-icon">❤️</span>
                            <span>Baby-gerecht</span>
                        </div>
                        <div class="federwiegen-feature">
                            <span class="federwiegen-feature-icon">📱</span>
                            <span>App-Steuerung</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="federwiegen-price-display" id="federwiegen-price-display" style="display: none;">
                <div class="federwiegen-price-content">
                    <p class="federwiegen-price-label">Monatlicher Mietpreis</p>
                    <div class="federwiegen-price-wrapper">
                        <span class="federwiegen-original-price" id="federwiegen-original-price" style="display: none;"></span>
                        <span class="federwiegen-final-price" id="federwiegen-final-price">0,00€</span>
                        <span class="federwiegen-price-period">/Monat</span>
                    </div>
                    <p class="federwiegen-savings" id="federwiegen-savings" style="display: none;"></p>
                    <div class="federwiegen-shipping-info">
                        <p class="federwiegen-shipping-text">
                            <span class="federwiegen-shipping-icon">🚚</span>
                            Einmalige Versandkosten: <strong><?php echo number_format($shipping_cost, 2, ',', '.'); ?>€</strong>
                        </p>
                    </div>
                </div>
                <div class="federwiegen-price-icon">💶</div>
            </div>
        </div>

        <div class="federwiegen-right">
            <div class="federwiegen-configuration">
                <!-- Variants Selection -->
                <?php if (!empty($variants)): ?>
                <div class="federwiegen-section">
                    <h3>Wählen Sie Ihre Ausführung</h3>
                    <div class="federwiegen-options variants layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($variants as $variant): ?>
                        <div class="federwiegen-option <?php echo !($variant->available ?? 1) ? 'unavailable' : ''; ?>" 
                             data-type="variant" 
                             data-id="<?php echo esc_attr($variant->id); ?>"
                             data-available="<?php echo esc_attr(($variant->available ?? 1) ? 'true' : 'false'); ?>"
                             data-images="<?php echo esc_attr(json_encode(array(
                                 $variant->image_url_1 ?? '',
                                 $variant->image_url_2 ?? '',
                                 $variant->image_url_3 ?? '',
                                 $variant->image_url_4 ?? '',
                                 $variant->image_url_5 ?? ''
                             ))); ?>">
                            <div class="federwiegen-option-content">
                                <h4><?php echo esc_html($variant->name); ?></h4>
                                <p><?php echo esc_html($variant->description); ?></p>
                                <?php
                                    $display_price = ($variant->price_from > 0) ? $variant->price_from : $variant->base_price;
                                    $prefix = ($variant->price_from > 0) ? 'ab ' : '';
                                ?>
                                <p class="federwiegen-option-price"><?php echo $prefix . number_format($display_price, 2, ',', '.'); ?>€/Monat</p>
                                <?php if (!($variant->available ?? 1)): ?>
                                    <div class="federwiegen-availability-notice">
                                        <span class="federwiegen-unavailable-badge">❌ Nicht verfügbar</span>
                                        <?php if (!empty($variant->availability_note)): ?>
                                            <p class="federwiegen-availability-note"><?php echo esc_html($variant->availability_note); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Extras Selection -->
                <?php if (!empty($extras)): ?>
                <div class="federwiegen-section" id="extras-section">
                    <h3>Wählen Sie Ihre Extras</h3>
                    <div class="federwiegen-options extras layout-<?php echo esc_attr($layout_style); ?>" id="extras-container">
                        <?php foreach ($extras as $extra): ?>
                        <div class="federwiegen-option" data-type="extra" data-id="<?php echo esc_attr($extra->id); ?>"
                             data-extra-image="<?php echo esc_attr($extra->image_url ?? ''); ?>"
                             data-available="true">
                            <div class="federwiegen-option-content">
                                <span class="federwiegen-extra-name"><?php echo esc_html($extra->name); ?></span>
                                <?php if ($extra->price > 0): ?>
                                <div class="federwiegen-extra-price">+<?php echo number_format($extra->price, 2, ',', '.'); ?>€/Monat</div>
                                <?php endif; ?>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Duration Selection -->
                <?php if (!empty($durations)): ?>
                <div class="federwiegen-section">
                    <h3>
                        Wählen Sie Ihre Mietdauer
                        <span class="federwiegen-tooltip">
                            ℹ️
                            <span class="federwiegen-tooltiptext"><?php echo esc_html($duration_tooltip); ?></span>
                        </span>
                    </h3>
                    <div class="federwiegen-options durations layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($durations as $duration): ?>
                        <div class="federwiegen-option" data-type="duration" data-id="<?php echo esc_attr($duration->id); ?>">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-duration-header">
                                    <span class="federwiegen-duration-name"><?php echo esc_html($duration->name); ?></span>
                                    <?php if ($duration->discount > 0): ?>
                                    <span class="federwiegen-discount-badge">-<?php echo round($duration->discount * 100); ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <p class="federwiegen-duration-info">
                                    Mindestlaufzeit: <?php echo $duration->months_minimum; ?> Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?>
                                </p>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Condition Selection (initially populated, will be updated via AJAX) -->
                <div class="federwiegen-section" id="condition-section" style="<?php echo esc_attr(empty($initial_conditions) ? 'display: none;' : ''); ?>">
                    <h3>
                        Zustand
                        <span class="federwiegen-tooltip">
                            ℹ️
                            <span class="federwiegen-tooltiptext"><?php echo esc_html($condition_tooltip); ?></span>
                        </span>
                    </h3>
                    <div class="federwiegen-options conditions layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_conditions as $condition): ?>
                        <div class="federwiegen-option" data-type="condition" data-id="<?php echo esc_attr($condition->id); ?>" data-available="true">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-condition-header">
                                    <span class="federwiegen-condition-name"><?php echo esc_html($condition->name); ?></span>
                                    <?php if ($condition->price_modifier != 0): ?>
                                    <span class="federwiegen-condition-badge">
                                        <?php echo $condition->price_modifier > 0 ? '+' : ''; ?><?php echo round($condition->price_modifier * 100); ?>%
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="federwiegen-condition-info"><?php echo esc_html($condition->description); ?></p>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Product Color Selection (initially populated, will be updated via AJAX) -->
                <div class="federwiegen-section" id="product-color-section" style="<?php echo esc_attr(empty($initial_product_colors) ? 'display: none;' : ''); ?>">
                    <h3>Produktfarbe</h3>
                    <small id="selected-product-color-name" class="federwiegen-selected-color-name"></small>
                    <div class="federwiegen-options product-colors layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_product_colors as $color): ?>
                        <div class="federwiegen-option" data-type="product-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-color-display">
                                    <div class="federwiegen-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Frame Color Selection (initially populated, will be updated via AJAX) -->
                <div class="federwiegen-section" id="frame-color-section" style="<?php echo esc_attr(empty($initial_frame_colors) ? 'display: none;' : ''); ?>">
                    <h3>Gestellfarbe</h3>
                    <small id="selected-frame-color-name" class="federwiegen-selected-color-name"></small>
                    <div class="federwiegen-options frame-colors layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_frame_colors as $color): ?>
                        <div class="federwiegen-option" data-type="frame-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-color-display">
                                    <div class="federwiegen-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Rent Button -->
                <div class="federwiegen-button-section">
                    <?php 
                    $required_selections = array();
                    if (!empty($variants)) $required_selections[] = 'variant';
                    if (!empty($extras)) $required_selections[] = 'extra';
                    if (!empty($durations)) $required_selections[] = 'duration';
                    // Note: conditions and colors are optional and will be checked dynamically
                    ?>
                    
                    <?php if (!empty($required_selections)): ?>
                    <button id="federwiegen-rent-button" class="federwiegen-rent-button" disabled>
                        <?php if (!empty($button_icon)): ?>
                            <img src="<?php echo esc_url($button_icon); ?>" alt="Button Icon" class="federwiegen-button-icon-img">
                        <?php else: ?>
                            <span class="federwiegen-button-icon">🛒</span>
                        <?php endif; ?>
                        <span><?php echo esc_html($button_text); ?></span>
                    </button>
                    <p class="federwiegen-button-help" id="federwiegen-button-help">
                        Bitte treffen Sie alle Auswahlen um fortzufahren
                    </p>
                    <p class="federwiegen-unavailable-help" id="federwiegen-unavailable-help" style="display: none;">
                        Das gewählte Produkt ist aktuell nicht verfügbar
                    </p>
                    <?php if (!empty($payment_icons)): ?>
                    <div class="federwiegen-payment-icons">
                        <?php foreach ($payment_icons as $icon): ?>
                            <img src="<?php echo esc_url(FEDERWIEGEN_PLUGIN_URL . 'assets/payment-icons/' . $icon . '.svg'); ?>" alt="<?php echo esc_attr($icon); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div style="padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; text-align: center;">
                        <h4>⚠️ Kategorie noch nicht vollständig konfiguriert</h4>
                        <p>Für diese Produktkategorie sind noch nicht alle erforderlichen Daten hinterlegt.</p>
                        <p><strong>Bitte konfigurieren Sie die fehlenden Daten im Admin-Bereich.</strong></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="federwiegen-features-section">
        <h3><?php echo esc_html($features_title); ?></h3>
        <div class="federwiegen-features-grid">
            <div class="federwiegen-feature-item">
                <div class="federwiegen-feature-icon-large">
                    <?php if (!empty($feature_1_icon)): ?>
                        <img src="<?php echo esc_url($feature_1_icon); ?>" alt="<?php echo esc_attr($feature_1_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        🛡️
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_1_title); ?></h4>
                <p><?php echo esc_html($feature_1_description); ?></p>
            </div>
            <div class="federwiegen-feature-item">
                <div class="federwiegen-feature-icon-large">
                    <?php if (!empty($feature_2_icon)): ?>
                        <img src="<?php echo esc_url($feature_2_icon); ?>" alt="<?php echo esc_attr($feature_2_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        ❤️
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_2_title); ?></h4>
                <p><?php echo esc_html($feature_2_description); ?></p>
            </div>
            <div class="federwiegen-feature-item">
                <div class="federwiegen-feature-icon-large">
                    <?php if (!empty($feature_3_icon)): ?>
                        <img src="<?php echo esc_url($feature_3_icon); ?>" alt="<?php echo esc_attr($feature_3_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        📱
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_3_title); ?></h4>
                <p><?php echo esc_html($feature_3_description); ?></p>
            </div>
        </div>
    </div>
</div>
