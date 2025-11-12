<?php

if (!defined('ABSPATH')) {
    exit;
}

if(!$options['opt-telegram-active-spot-land']) { return; }

define('SPOTPLAY_TG_TOKEN', $options['opt-telegram-token-spot-land']); // توکن ربات تلگرام

define('SPOTPLAY_TG_ADMIN_CHAT_ID', $options['opt-telegram-admin-chat-id-spot-land']); // آی‌دی چت ادمین


function spl_tg_api_request($method, $body)
{
    $token = SPOTPLAY_TG_TOKEN;
    if (empty($token)) { return false; }
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $args = [
        'timeout' => 10,
        'body'    => $body,
    ];
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) { return false; }
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) { return false; }
    $resp_body = wp_remote_retrieve_body($response);
    $decoded = json_decode($resp_body, true);
    return is_array($decoded) ? $decoded : false;
}

function spl_tg_build_order_keyboard($order_id)
{
    $nonce = wp_create_nonce('spotplay_tg_order_action_' . $order_id);
    $approve_url = add_query_arg([
        'action'   => 'spotplay_tg_order_action',
        'order_id' => $order_id,
        'op'       => 'approve',
        'nonce'    => $nonce,
    ], admin_url('admin-ajax.php'));
    $cancel_url = add_query_arg([
        'action'   => 'spotplay_tg_order_action',
        'order_id' => $order_id,
        'op'       => 'cancel',
        'nonce'    => $nonce,
    ], admin_url('admin-ajax.php'));

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => 'تایید سفارش و ایجاد لایسنس', 'url' => $approve_url],
            ],
            [
                ['text' => 'لغو سفارش', 'url' => $cancel_url],
            ],
        ],
    ];
    return wp_json_encode($keyboard, JSON_UNESCAPED_UNICODE);
}

function spl_tg_send_order_notification($order_id)
{
    if (empty(SPOTPLAY_TG_TOKEN) || empty(SPOTPLAY_TG_ADMIN_CHAT_ID)) { return false; }
    $order = wc_get_order($order_id);
    if (!$order) { return false; }

    $first = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : '';
    $last  = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : '';
    $phone = method_exists($order, 'get_billing_phone') ? $order->get_billing_phone() : (string) $order->get_meta('_billing_phone');
    $name  = trim($first . ' ' . $last);

    $caption = "سفارش جدید در انتظار تایید رسید\n";
    $caption .= "سفارش #{$order_id}\n";
    $caption .= "نام: " . ($name ?: '—') . "\n";
    $caption .= "شماره: " . ($phone ?: '—');

    $attachment_id = (int) get_post_meta($order_id, '_receipt_attachment_id', true);
    $photo_url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : '';
    $reply_markup = spl_tg_build_order_keyboard($order_id);

    if (!empty($photo_url)) {
        $body = [
            'chat_id'     => SPOTPLAY_TG_ADMIN_CHAT_ID,
            'photo'       => $photo_url,
            'caption'     => $caption,
            'parse_mode'  => 'HTML',
            'reply_markup'=> $reply_markup,
        ];
        return spl_tg_api_request('sendPhoto', $body);
    } else {
        $body = [
            'chat_id'     => SPOTPLAY_TG_ADMIN_CHAT_ID,
            'text'        => $caption,
            'parse_mode'  => 'HTML',
            'reply_markup'=> $reply_markup,
        ];
        return spl_tg_api_request('sendMessage', $body);
    }
}

function spl_tg_order_action()
{
    $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
    $op = isset($_GET['op']) ? sanitize_text_field(wp_unslash($_GET['op'])) : '';
    $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
    if (!$order_id || !$op || !$nonce || !wp_verify_nonce($nonce, 'spotplay_tg_order_action_' . $order_id)) {
        wp_die(__('درخواست نامعتبر است.', 'spotplayer-landing'));
    }

    if (!current_user_can('manage_woocommerce')) {
        auth_redirect();
    }

    $order = wc_get_order($order_id);
    if (!$order) { wp_die(__('سفارش یافت نشد.', 'spotplayer-landing')); }

    if ($op === 'approve') {
        $order->set_status('completed');
        $order->save();
        if (function_exists('spl_handle_order_completed')) {
            spl_handle_order_completed($order_id);
        }
        echo esc_html(__('سفارش تایید و لایسنس ایجاد شد.', 'spotplayer-landing'));
    } elseif ($op === 'cancel') {
        $order->set_status('cancelled');
        $order->save();
        echo esc_html(__('سفارش لغو شد.', 'spotplayer-landing'));
    } else {
        echo esc_html(__('عملیات نامعتبر.', 'spotplayer-landing'));
    }
    wp_die();
}
add_action('wp_ajax_spotplay_tg_order_action', 'spl_tg_order_action');