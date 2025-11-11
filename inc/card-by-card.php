<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Card-to-card flow:
 * - Register custom order status "در انتظار تایید رسید"
 * - AJAX endpoint to create order and attach bank receipt image
 */

/**
 * Register custom WooCommerce order status for awaiting receipt approval.
 */
function spl_register_awaiting_receipt_status()
{
    register_post_status('wc-awaiting-receipt', [
        'label' => _x('در انتظار تایید رسید', 'Order status', 'spotplayer-landing'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('در انتظار تایید رسید <span class="count">(%s)</span>', 'در انتظار تایید رسید <span class="count">(%s)</span>'),
    ]);
}
add_action('init', 'spl_register_awaiting_receipt_status');

/**
 * Insert the custom status into WooCommerce list.
 */
function spl_add_awaiting_receipt_to_statuses($order_statuses)
{
    $new_statuses = [];
    foreach ($order_statuses as $key => $label) {
        $new_statuses[$key] = $label;
        if ('wc-pending' === $key) {
            $new_statuses['wc-awaiting-receipt'] = _x('در انتظار تایید رسید', 'Order status', 'spotplayer-landing');
        }
    }
    return $new_statuses;
}
add_filter('wc_order_statuses', 'spl_add_awaiting_receipt_to_statuses');

/**
 * Handle AJAX card-to-card receipt submission.
 */
function spl_ajax_card_submit()
{
    check_ajax_referer('spotplayer_landing_nonce', 'nonce');

    $full_name = isset($_POST['fullName']) ? sanitize_text_field(wp_unslash($_POST['fullName'])) : '';
    $phone = isset($_POST['phone']) ? preg_replace('/\D+/', '', wp_unslash($_POST['phone'])) : '';
    $coupon = isset($_POST['coupon']) ? sanitize_text_field(wp_unslash($_POST['coupon'])) : '';
    // Read product id from Codestar settings with legacy fallback
    $csf = get_option('spotplay_land');
    $product_id = 0;
    if (is_array($csf) && !empty($csf['opt-product_id'])) {
        $product_id = absint($csf['opt-product_id']);
    }
    if (!$product_id) {
        $product_id = absint(get_option('product_id'));
    }

    if (empty($full_name) || empty($phone) || !$product_id) {
        wp_send_json_error(['message' => __('اطلاعات ورودی نامعتبر است.', 'spotplayer-landing')], 400);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => __('محصول معتبر نیست.', 'spotplayer-landing')], 400);
    }

    // Create order
    try {
        $order = wc_create_order();
        $order->add_product($product, 1);
        // Ensure totals reflect product price
        if (method_exists($order, 'calculate_totals')) {
            $order->calculate_totals();
        }

        // Split full name into first/last (best effort)
        $parts = preg_split('/\s+/', trim($full_name));
        $first = isset($parts[0]) ? $parts[0] : '';
        $last  = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';

        if (method_exists($order, 'set_billing_first_name')) {
            $order->set_billing_first_name($first);
        }
        if (method_exists($order, 'set_billing_last_name')) {
            $order->set_billing_last_name($last);
        }
        if (method_exists($order, 'set_billing_phone')) {
            $order->set_billing_phone($phone);
        } else {
            $order->update_meta_data('_billing_phone', $phone);
        }

        if (!empty($coupon)) {
            // Store coupon for reference; actual discount application left to manual review.
            $order->update_meta_data('_submitted_coupon', $coupon);
        }

        $order->set_status('wc-awaiting-receipt');
        $order->save();
        $order_id = $order->get_id();

        // Handle receipt image upload
        $attachment_id = 0;
        if (!empty($_FILES['receipt']) && isset($_FILES['receipt']['tmp_name'])) {
            // Restrict mime types and size
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_type = isset($_FILES['receipt']['type']) ? $_FILES['receipt']['type'] : '';
            if (!in_array($file_type, $allowed_types, true)) {
                wp_send_json_error(['message' => __('فرمت تصویر باید JPG یا PNG باشد.', 'spotplayer-landing')], 400);
            }

            $max_size = 2 * 1024 * 1024; // 2MB
            $file_size = isset($_FILES['receipt']['size']) ? (int) $_FILES['receipt']['size'] : 0;
            if ($file_size <= 0 || $file_size > $max_size) {
                wp_send_json_error(['message' => __('حجم فایل باید حداکثر ۲ مگابایت باشد.', 'spotplayer-landing')], 400);
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('receipt', 0);
            if (is_wp_error($attachment_id)) {
                wp_send_json_error(['message' => __('آپلود رسید با خطا مواجه شد.', 'spotplayer-landing')], 500);
            }
            update_post_meta($order_id, '_receipt_attachment_id', $attachment_id);
        }

        // Notify admin
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, __('رسید کارت به کارت جدید', 'spotplayer-landing'), sprintf(__('سفارش #%d در وضعیت در انتظار تایید رسید ایجاد شد.', 'spotplayer-landing'), $order_id));

        wp_send_json_success([
            'order_id' => $order_id,
            'attachment_id' => $attachment_id,
            'message' => __('سفارش شما ثبت شد و پس از بررسی رسید تایید خواهد شد.', 'spotplayer-landing'),
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => __('ایجاد سفارش ناموفق بود.', 'spotplayer-landing')], 500);
    }
}

add_action('wp_ajax_spotplayer_landing_card_submit', 'spl_ajax_card_submit');
add_action('wp_ajax_nopriv_spotplayer_landing_card_submit', 'spl_ajax_card_submit');

/**
 * Admin: show uploaded bank receipt on order edit screen.
 */
function spl_admin_order_receipt_panel($order)
{
    if (!$order instanceof WC_Order) { return; }
    $order_id = $order->get_id();
    $attachment_id = (int) get_post_meta($order_id, '_receipt_attachment_id', true);
    if ($attachment_id <= 0) { return; }

    $thumb = wp_get_attachment_image($attachment_id, 'thumbnail', false, ['style' => 'border:1px solid #ddd; border-radius:4px;']);
    $url = wp_get_attachment_url($attachment_id);
    echo '<div class="order-receipt-panel" style="margin-top:15px">';
    echo '<h4>' . esc_html(__('رسید کارت به کارت', 'spotplayer-landing')) . '</h4>';
    if ($thumb) { echo '<div>' . $thumb . '</div>'; }
    if ($url) { echo '<p><a href="' . esc_url($url) . '" target="_blank">' . esc_html(__('مشاهده تصویر کامل', 'spotplayer-landing')) . '</a></p>'; }
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_order_details', 'spl_admin_order_receipt_panel');