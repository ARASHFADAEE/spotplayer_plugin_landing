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
    // Read SpotPlayer settings via Codestar options (fallback to legacy options)
    $settings = spl_get_spotplayer_settings();
    $api_key = isset($settings['api_key']) ? (string)$settings['api_key'] : '';
    $level   = isset($settings['level']) ? (string)$settings['level'] : '-1';

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
    $options = get_option('spotplay_land');
    $provider = is_array($options) && isset($options['opt-sms-provider']) ? (string)$options['opt-sms-provider'] : 'melipayamak';

    $to = preg_replace('/\D+/', '', (string) $phone);
    if (empty($to) || empty($license_key)) {
        return '';
    }

    if ($provider === 'melipayamak') {
        $username = is_array($options) && isset($options['opt-username-melipayamak']) ? (string)$options['opt-username-melipayamak'] : '';
        $password = is_array($options) && isset($options['opt-password-melipayamak']) ? (string)$options['opt-password-melipayamak'] : '';
        $pattern  = is_array($options) && isset($options['opt-pattern-melipayamak']) ? (string)$options['opt-pattern-melipayamak'] : '';

        if (empty($username) || empty($password) || empty($pattern)) {
            return '';
        }

        $text = '@' . $pattern . '@' . $license_key;
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
        }

        return $result;
    }

    if ($provider === 'kavenegar') {
        $api_key = is_array($options) && isset($options['opt-kavenegar-api_key']) ? (string)$options['opt-kavenegar-api_key'] : '';
        $template = is_array($options) && isset($options['opt-kavenegar-pattern']) ? (string)$options['opt-kavenegar-pattern'] : '';

        if (empty($api_key) || empty($template)) {
            return '';
        }

        $endpoint = sprintf('https://api.kavenegar.com/v1/%s/verify/lookup.json', rawurlencode($api_key));
        $query = [
            'receptor' => $to,
            'token'    => (string)$license_key,
            'template' => $template,
        ];
        $url = $endpoint . '?' . http_build_query($query);

        $result = '';
        try {
            $response = wp_remote_get($url, ['timeout' => 15]);
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $result = (string)$body;
            }
        } catch (Exception $e) {
        }
        return $result;
    }

    return '';
}

/**
 * Parse courses option (comma or newline separated IDs) into array.
 */
function spl_get_courses_option(): array
{
    // Prefer Codestar group option
    $opt = get_option('spotplay_land');
    if (is_array($opt) && !empty($opt['opt-spotplayer-courses']) && is_array($opt['opt-spotplayer-courses'])) {
        $courses = [];
        foreach ($opt['opt-spotplayer-courses'] as $item) {
            if (is_array($item) && !empty($item['course_id'])) {
                $cid = trim((string)$item['course_id']);
                if ($cid !== '') {
                    $courses[] = $cid;
                }
            }
        }
        if (!empty($courses)) {
            return array_values($courses);
        }
    }
    // Fallback to legacy text/textarea option
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
    $mapped_product_id = spl_get_mapped_product_id();
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

    // Pre-check settings
    $settings = spl_get_spotplayer_settings();
    if (empty($settings['api_key'])) {
        $order->add_order_note(__('کلید API اسپات پلیر تنظیم نشده است؛ لایسنس ساخته نشد.', 'spotplayer-landing'));
        $order->update_meta_data('_spotplayer_license_error', 'missing_api_key');
        $order->save();
        return;
    }

    $courses = spl_get_courses_option();
    if (empty($courses)) {
        $order->add_order_note(__('هیچ دوره‌ای در تنظیمات اسپات پلیر انتخاب نشده؛ لایسنس ساخته نشد.', 'spotplayer-landing'));
        $order->update_meta_data('_spotplayer_license_error', 'missing_courses');
        $order->save();
        return;
    }

    $name = trim($order->get_formatted_billing_full_name());
    $phone = (string)$order->get_billing_phone();
    if ($phone === '') {
        $order->add_order_note(__('شماره موبایل برای سفارش یافت نشد؛ لطفاً شماره را وارد و دوباره تلاش کنید.', 'spotplayer-landing'));
        $order->update_meta_data('_spotplayer_license_error', 'missing_phone');
        $order->save();
        return;
    }

    // Read test mode from Codestar option, fallback to legacy option
    $opt = get_option('spotplay_land');
    $is_test = is_array($opt) && !empty($opt['opt-spotplayer-test_mode']) ? (bool)$opt['opt-spotplayer-test_mode'] : (bool)get_option('spotplayer_test_mode', false);
    // Idempotency: avoid creating duplicate license if already exists
    $license_key = (string) $order->get_meta('_spotplayer_license_key');

    // Create SpotPlayer license
    try {
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
            } else {
                $order->add_order_note(__('پاسخ نامعتبر از سرویس اسپات پلیر؛ لایسنس ساخته نشد.', 'spotplayer-landing'));
                $order->update_meta_data('_spotplayer_license_error', 'invalid_response');
                $order->save();
                return;
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
        // Surface error to admin via order note
        $order->add_order_note(sprintf(__('خطا در ساخت لایسنس: %s', 'spotplayer-landing'), $e->getMessage()));
        $order->update_meta_data('_spotplayer_license_error', $e->getMessage());
        $order->save();
        if (function_exists('error_log')) {
            error_log('[SpotPlayer] License creation failed: ' . $e->getMessage());
        }
    }
}

/**
 * Normalize SpotPlayer settings from Codestar options with legacy fallbacks.
 *
 * @return array{api_key:string,level:string,courses:array,test_mode:bool}
 */
function spl_get_spotplayer_settings(): array
{
    $opt = get_option('spotplay_land');
    $api_key = '';
    $level = '-1';
    $test_mode = false;
    $courses = [];

    if (is_array($opt)) {
        if (isset($opt['opt-spotplayer-api_key'])) {
            $api_key = (string)$opt['opt-spotplayer-api_key'];
        }
        if (isset($opt['opt-spotplayer-level'])) {
            $level = (string)$opt['opt-spotplayer-level'];
        }
        if (!empty($opt['opt-spotplayer-test_mode'])) {
            $test_mode = (bool)$opt['opt-spotplayer-test_mode'];
        }
        if (!empty($opt['opt-spotplayer-courses']) && is_array($opt['opt-spotplayer-courses'])) {
            foreach ($opt['opt-spotplayer-courses'] as $item) {
                if (is_array($item) && !empty($item['course_id'])) {
                    $cid = trim((string)$item['course_id']);
                    if ($cid !== '') {
                        $courses[] = $cid;
                    }
                }
            }
        }
    }

    // Fallbacks to legacy options if Codestar values are not set
    if ($api_key === '') {
        $api_key = (string)get_option('spotplayer_api_key');
    }
    if ($level === '' || $level === null) {
        $level = (string)get_option('spotplayer_level', '-1');
    }
    if (empty($courses)) {
        $courses = spl_get_courses_option();
    }
    if ($test_mode === false) {
        $test_mode = (bool)get_option('spotplayer_test_mode', false);
    }

    return [
        'api_key'   => $api_key,
        'level'     => $level,
        'courses'   => $courses,
        'test_mode' => $test_mode,
    ];
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

/**
 * Admin: add manual order action to create license.
 */
function spl_admin_add_license_action($actions, $order)
{
    if ($order instanceof WC_Order && spl_order_has_mapped_product($order)) {
        $actions['spotplayer_create_license'] = __('ایجاد لایسنس برای این کاربر', 'spotplayer-landing');
    }
    return $actions;
}
add_filter('woocommerce_order_actions', 'spl_admin_add_license_action', 10, 2);

/**
 * Handle manual license creation action from order admin.
 */
function woocommerce_order_action_spotplayer_create_license($order)
{
    if ($order instanceof WC_Order) {
        $order_id = $order->get_id();
        $existing = (string) $order->get_meta('_spotplayer_license_key');
        if ($existing !== '') {
            $order->add_order_note(sprintf(__('لایسنس قبلاً ایجاد شده است: %s', 'spotplayer-landing'), $existing));
            $order->save();
            return;
        }
        $order->add_order_note(__('ایجاد لایسنس به‌صورت دستی آغاز شد.', 'spotplayer-landing'));
        $order->save();
        spl_handle_order_completed($order_id);
        $new_key = (string) $order->get_meta('_spotplayer_license_key');
        if ($new_key !== '') {
            $order->add_order_note(sprintf(__('لایسنس با موفقیت ایجاد شد: %s', 'spotplayer-landing'), $new_key));
        } else {
            $err = (string) $order->get_meta('_spotplayer_license_error');
            $order->add_order_note(sprintf(__('ایجاد لایسنس ناموفق بود. خطا: %s', 'spotplayer-landing'), $err ?: __('نامشخص', 'spotplayer-landing')));
        }
        $order->save();
    }
}
add_action('woocommerce_order_action_spotplayer_create_license', 'woocommerce_order_action_spotplayer_create_license', 10, 1);
/**
 * Get mapped WooCommerce product ID from Codestar settings (fallback to legacy option).
 */
function spl_get_mapped_product_id(): int {
    $csf = get_option('spotplay_land');
    if (is_array($csf)) {
        $pid = isset($csf['opt-product_id']) ? absint($csf['opt-product_id']) : 0;
        if ($pid > 0) {
            return $pid;
        }
    }
    return absint(get_option('product_id'));
}