<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SpotPlayer API integration
 * - Provides helper to call SpotPlayer API
 * - Creates license on order completion for mapped products
 * - Registers/creates user account on successful payment if not existing
 */

/**
 * Build filtered array removing nulls.
 */
function spl_filter_array($array)
{
    return array_filter($array, function ($v) {
        return !is_null($v);
    });
}


// Note: Avoid calling WooCommerce functions at file load.
// Product rendering is handled inside the shortcode template.

/**
 * Perform HTTP request to SpotPlayer API.
 *
 * @param string $url
 * @param array|null $payload
 * @return array|null
 * @throws Exception
 */
function spl_request($url, $payload = null)
{
    $api_key = get_option('spotplayer_api_key');
    $level   = get_option('spotplayer_level', '-1');

    if (empty($api_key)) {
        throw new Exception(__('SpotPlayer API key is not configured.', 'spotplayer-landing'));
    }

    $headers = [
        '$API: ' . $api_key,
        '$LEVEL: ' . (string)$level,
        'content-type: application/json',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $payload ? 'POST' : 'GET',
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(spl_filter_array($payload)));
    }

    $resp = curl_exec($ch);
    $json = json_decode($resp, true);
    curl_close($ch);

    if (is_array($json) && isset($json['ex'])) {
        $msg = is_array($json['ex']) && isset($json['ex']['msg']) ? $json['ex']['msg'] : __('Unknown SpotPlayer error', 'spotplayer-landing');
        throw new Exception($msg);
    }

    return $json;
}

/**
 * Create SpotPlayer license using phone as watermark text.
 *
 * @param string $customer_name
 * @param array $courses
 * @param string $phone
 * @param bool $is_test
 * @return array|null
 */
function spl_create_license($customer_name, $courses, $phone, $is_test = false)
{
    if (empty($courses)) {
        return null;
    }

    $payload = [
        'test' => (bool)$is_test,
        'name' => (string)$customer_name,
        'course' => array_values($courses),
        'watermark' => [
            'texts' => [
                ['text' => (string)$phone],
            ],
        ],
    ];

    return spl_request('https://panel.spotplayer.ir/license/edit/', $payload);
}

/**
 * Send license key via Meli Payamak (SendByBaseNumber3 pattern method).
 *
 * @param string $phone
 * @param string $license_key
 * @return string Result string (recId or error code), empty on failure
 */
function spl_send_license_sms($phone, $license_key)
{
    $username = (string) get_option('meli_username');
    $password = (string) get_option('meli_password');
    $body_id  = (string) get_option('meli_body_id');

    $to = preg_replace('/\D+/', '', (string) $phone);
    if (empty($username) || empty($password) || empty($body_id) || empty($to) || empty($license_key)) {
        return '';
    }

    $text = '@' . $body_id . '@' . $license_key;
    $result = '';

    try {
        if (class_exists('SoapClient')) {
            ini_set('soap.wsdl_cache_enabled', '0');
            $client = new SoapClient('http://api.payamak-panel.com/post/Send.asmx?wsdl', ['encoding' => 'UTF-8']);
            $data = [
                'username' => $username,
                'password' => $password,
                'text' => $text,
                'to' => $to,
            ];
            $resp = $client->SendByBaseNumber3($data);
            if (is_object($resp) && isset($resp->SendByBaseNumber3Result)) {
                $result = (string) $resp->SendByBaseNumber3Result;
            }
        } else {
            $url = 'http://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber3?' . http_build_query([
                'username' => $username,
                'password' => $password,
                'text' => $text,
                'to' => $to,
            ]);
            $response = wp_remote_get($url, ['timeout' => 15]);
            if (!is_wp_error($response)) {
                $result = (string) wp_remote_retrieve_body($response);
            }
        }
    } catch (Exception $e) {
        // Silent failure; result remains empty
    }

    return $result;
}

/**
 * Parse courses option (comma or newline separated IDs) into array.
 */
function spl_get_courses_option(): array
{
    $raw = (string)get_option('spotplayer_courses');
    if (empty($raw)) {
        return [];
    }
    $parts = preg_split('/[\n,]+/', $raw);
    return array_values(array_filter(array_map('trim', $parts)));
}

/**
 * Determine if an order contains the mapped product that should trigger license creation.
 */
function spl_order_has_mapped_product(WC_Order $order): bool
{
    $mapped_product_id = absint(get_option('product_id'));
    if (!$mapped_product_id) {
        return false;
    }

    // Only look at line items. Read IDs from item data/meta to avoid undefined methods.
    foreach ($order->get_items('line_item') as $item_id => $item) {
        $product_id = 0;
        $variation_id = 0;

        if (is_object($item) && method_exists($item, 'get_data')) {
            $data = (array) $item->get_data();
            $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $variation_id = isset($data['variation_id']) ? (int)$data['variation_id'] : 0;
        } else {
            // Fallback: read from item meta
            if (function_exists('wc_get_order_item_meta')) {
                $product_id = (int) wc_get_order_item_meta($item_id, '_product_id', true);
                $variation_id = (int) wc_get_order_item_meta($item_id, '_variation_id', true);
            }
        }

        if ($product_id && $product_id === $mapped_product_id) {
            return true;
        }

        // If a variation is purchased, compare parent product via post parent id
        if ($variation_id) {
            $parent_id = (int) wp_get_post_parent_id($variation_id);
            if ($parent_id && $parent_id === $mapped_product_id) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Ensure user account exists after successful payment.
 * If the order has no user, create one based on billing info.
 */
function spl_ensure_user_for_order(WC_Order $order): int
{
    $user_id = (int)$order->get_user_id();
    if ($user_id > 0) {
        return $user_id;
    }

    $email = (string)$order->get_billing_email();
    $first = (string)$order->get_billing_first_name();
    $last  = (string)$order->get_billing_last_name();
    $phone = (string)$order->get_billing_phone();

    if (empty($email)) {
        // Fallback email if not provided
        $email = 'user' . preg_replace('/\D+/', '', $phone) . '@example.com';
    }

    $login = sanitize_user('user_' . preg_replace('/\D+/', '', $phone));
    if (username_exists($login)) {
        $u = get_user_by('login', $login);
        $user_id = $u ? (int)$u->ID : 0;
        return $user_id;
    }

    $password = wp_generate_password(12, true);
    $user_id = wp_insert_user([
        'user_login' => $login,
        'user_pass'  => $password,
        'user_email' => $email,
        'first_name' => $first,
        'last_name'  => $last,
    ]);

    if (!is_wp_error($user_id)) {
        // Optionally notify user via email
        wp_new_user_notification($user_id, null, 'both');
    }

    return is_wp_error($user_id) ? 0 : (int)$user_id;
}

/**
 * Hook: create SpotPlayer license and ensure user registration on order completion.
 */
function spl_handle_order_completed($order_id)
{
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) {
        return;
    }

    // Only run for relevant product
    if (!spl_order_has_mapped_product($order)) {
        return;
    }

    // Ensure user account exists
    spl_ensure_user_for_order($order);

    // Create SpotPlayer license
    try {
        $courses = spl_get_courses_option();
        $name = trim($order->get_formatted_billing_full_name());
        $phone = (string)$order->get_billing_phone();
        $is_test = (bool)get_option('spotplayer_test_mode', false);
        // Idempotency: avoid creating duplicate license if already exists
        $license_key = (string) $order->get_meta('_spotplayer_license_key');

        if ($license_key === '') {
            $license = spl_create_license($name ?: 'customer', $courses, $phone, $is_test);
            if (is_array($license) && isset($license['key'])) {
                $license_key = (string)$license['key'];
                $order->update_meta_data('_spotplayer_license_id', isset($license['_id']) ? $license['_id'] : '');
                $order->update_meta_data('_spotplayer_license_key', $license_key);
                $order->update_meta_data('_spotplayer_license_url', isset($license['url']) ? $license['url'] : '');
                $order->save();

                // Add order note with license key for admin visibility
                $order->add_order_note(sprintf(__('کلید لایسنس ساخته شد: %s', 'spotplayer-landing'), $license_key));
            }
        }

        // Send SMS with license key (pattern: @bodyId@arg1), only if not sent before
        if ($license_key !== '') {
            $already_sent = (string) $order->get_meta('_spotplayer_sms_result');
            if ($already_sent === '') {
                $sms_result = spl_send_license_sms($phone, $license_key);
                if ($sms_result !== '') {
                    $order->update_meta_data('_spotplayer_sms_result', $sms_result);
                    $order->add_order_note(sprintf(__('ارسال پیامک لایسنس: %s', 'spotplayer-landing'), $sms_result));
                    $order->save();
                } else {
                    $order->add_order_note(__('ارسال پیامک لایسنس ناموفق بود یا تنظیمات ناقص است.', 'spotplayer-landing'));
                }
            }
        }
    } catch (Exception $e) {
        // Log error but do not break order completion
        if (function_exists('error_log')) {
            error_log('[SpotPlayer] License creation failed: ' . $e->getMessage());
        }
    }
}

add_action('woocommerce_order_status_completed', 'spl_handle_order_completed', 10, 1);

/**
 * Also trigger license creation when status changes to completed from any status.
 */
function spl_handle_status_changed($order_id, $old_status, $new_status, $order)
{
    // $new_status uses slug without 'wc-'
    if ($new_status === 'completed') {
        spl_handle_order_completed($order_id);
    }
}
add_action('woocommerce_order_status_changed', 'spl_handle_status_changed', 10, 4);