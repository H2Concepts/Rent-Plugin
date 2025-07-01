<?php
namespace FederwiegenVerleih;

class Ajax {
    
    public function ajax_get_product_price() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids) ? $extra_ids[0] : 0;
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
            $variant_id
        ));
        
        $extras = [];
        if (!empty($extra_ids)) {
            $placeholders = implode(',', array_fill(0, count($extra_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE id IN ($placeholders)",
                ...$extra_ids
            );
            $extras = $wpdb->get_results($query);
        }
        
        $duration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}federwiegen_durations WHERE id = %d",
            $duration_id
        ));
        
        $condition = null;
        if ($condition_id) {
            $condition = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d",
                $condition_id
            ));
        }
        
        // Find best matching Stripe link
        $link = $this->find_best_stripe_link($variant_id, $extra_id, $duration_id, $condition_id, $product_color_id, $frame_color_id);
        
        // Get shipping cost from category
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT shipping_cost FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d",
            $variant->category_id
        ));
        
        if ($variant && $duration) {
            $base_price = floatval($variant->base_price);
            foreach ($extras as $ex) {
                $base_price += floatval($ex->price);
            }
            
            // Apply condition price modifier
            if ($condition && $condition->price_modifier != 0) {
                $base_price = $base_price * (1 + floatval($condition->price_modifier));
            }
            
            $discount = floatval($duration->discount);
            $final_price = $base_price * (1 - $discount);
            $shipping_cost = $category ? floatval($category->shipping_cost) : 0;
            
            wp_send_json_success(array(
                'base_price' => $base_price,
                'final_price' => $final_price,
                'discount' => $discount,
                'shipping_cost' => $shipping_cost,
                'stripe_link' => $link ?: '#',
                'available' => $variant->available ? true : false,
                'availability_note' => $variant->availability_note ?: ''
            ));
        } else {
            wp_send_json_error('Invalid selection');
        }
    }
    
    private function find_best_stripe_link($variant_id, $extra_id, $duration_id, $condition_id = null, $product_color_id = null, $frame_color_id = null) {
        global $wpdb;
        
        // Try to find exact match first
        $exact_link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id = %d AND frame_color_id = %d",
            $variant_id, $extra_id, $duration_id, $condition_id, $product_color_id, $frame_color_id
        ));
        
        if ($exact_link) {
            return $exact_link;
        }
        
        // Try without frame color
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id = %d AND frame_color_id IS NULL",
            $variant_id, $extra_id, $duration_id, $condition_id, $product_color_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without product color
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id IS NULL AND frame_color_id = %d",
            $variant_id, $extra_id, $duration_id, $condition_id, $frame_color_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without both colors
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id = %d AND product_color_id IS NULL AND frame_color_id IS NULL",
            $variant_id, $extra_id, $duration_id, $condition_id
        ));
        
        if ($link) {
            return $link;
        }
        
        // Try without condition
        $link = $wpdb->get_var($wpdb->prepare(
            "SELECT stripe_link FROM {$wpdb->prefix}federwiegen_links 
             WHERE variant_id = %d AND extra_id = %d AND duration_id = %d 
             AND condition_id IS NULL AND product_color_id IS NULL AND frame_color_id IS NULL",
            $variant_id, $extra_id, $duration_id
        ));
        
        return $link;
    }
    
    public function ajax_get_variant_images() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        $variant = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url_1, image_url_2, image_url_3, image_url_4, image_url_5 FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
            $variant_id
        ));
        
        if ($variant) {
            $images = array();
            for ($i = 1; $i <= 5; $i++) {
                $image_field = 'image_url_' . $i;
                if (!empty($variant->$image_field)) {
                    $images[] = $variant->$image_field;
                }
            }
            
            wp_send_json_success(array(
                'images' => $images
            ));
        } else {
            wp_send_json_error('Variant not found');
        }
    }
    
    public function ajax_get_extra_image() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
        
        global $wpdb;
        
        $extra = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url FROM {$wpdb->prefix}federwiegen_extras WHERE id = %d",
            $extra_id
        ));
        
        if ($extra) {
            wp_send_json_success(array(
                'image_url' => $extra->image_url ?: ''
            ));
        } else {
            wp_send_json_error('Extra not found');
        }
    }
    
    public function ajax_get_variant_options() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        
        global $wpdb;
        
        // Get variant-specific options
        $variant_options = $wpdb->get_results($wpdb->prepare(
            "SELECT option_type, option_id, available FROM {$wpdb->prefix}federwiegen_variant_options WHERE variant_id = %d",
            $variant_id
        ));
        
        $conditions = array();
        $product_colors = array();
        $frame_colors = array();
        $extras = array();
        
        if (!empty($variant_options)) {
            // Get specific options for this variant
            foreach ($variant_options as $option) {
                switch ($option->option_type) {
                    case 'condition':
                        $condition = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE id = %d AND active = 1 AND available = 1",
                            $option->option_id
                        ));
                        if ($condition) {
                            $condition->available = (bool)$option->available;
                            $conditions[] = $condition;
                        }
                        break;
                    case 'product_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d AND active = 1 AND available = 1",
                            $option->option_id
                        ));
                        if ($color) {
                            $color->available = (bool)$option->available;
                            $product_colors[] = $color;
                        }
                        break;
                    case 'frame_color':
                        $color = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE id = %d AND active = 1 AND available = 1",
                            $option->option_id
                        ));
                        if ($color) {
                            $color->available = (bool)$option->available;
                            $frame_colors[] = $color;
                        }
                        break;
                    case 'extra':
                        $extra = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE id = %d AND active = 1",
                            $option->option_id
                        ));
                        if ($extra) {
                            $extra->available = (bool)$option->available;
                            $extras[] = $extra;
                        }
                        break;
                }
            }
        } else {
            // No specific options defined, get all available options for the category
            $variant = $wpdb->get_row($wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}federwiegen_variants WHERE id = %d",
                $variant_id
            ));
            
            if ($variant) {
                $conditions = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d AND active = 1 AND available = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($conditions as $c) { $c->available = true; }

                $product_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' AND active = 1 AND available = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($product_colors as $c) { $c->available = true; }

                $frame_colors = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' AND active = 1 AND available = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($frame_colors as $c) { $c->available = true; }

                $extras = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE category_id = %d AND active = 1 ORDER BY sort_order",
                    $variant->category_id
                ));
                foreach ($extras as $e) { $e->available = true; }
            }
        }

        wp_send_json_success(array(
            'conditions' => $conditions,
            'product_colors' => $product_colors,
            'frame_colors' => $frame_colors,
            'extras' => $extras
        ));
    }
    
    public function ajax_submit_order() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $variant_id = intval($_POST['variant_id']);
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
        $duration_id = intval($_POST['duration_id']);
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        $final_price = floatval($_POST['final_price']);
        $stripe_link = esc_url_raw($_POST['stripe_link']);
        
        global $wpdb;
        
        // Insert order
        $result = $wpdb->insert(
            $wpdb->prefix . 'federwiegen_orders',
            array(
                'category_id' => $category_id,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'extra_ids' => $extra_ids_raw,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'final_price' => $final_price,
                'stripe_link' => $stripe_link,
                'customer_name' => '',
                'customer_email' => '',
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array('order_id' => $wpdb->insert_id));
        } else {
            wp_send_json_error('Failed to save order');
        }
    }
    
    public function ajax_track_interaction() {
        check_ajax_referer('federwiegen_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $event_type = sanitize_text_field($_POST['event_type']);
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
        $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
        $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
        $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : null;
        $duration_id = isset($_POST['duration_id']) ? intval($_POST['duration_id']) : null;
        $condition_id = isset($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
        $product_color_id = isset($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
        $frame_color_id = isset($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'federwiegen_analytics',
            array(
                'category_id' => $category_id,
                'event_type' => $event_type,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'extra_ids' => $extra_ids_raw,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }
    
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
}
