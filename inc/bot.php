<?php

if (!defined('ABSPATH')) {
    exit;
}

// Read Codestar options safely
$options = get_option('spotplay_land');
if (!is_array($options)) { $options = array(); }
$tg_active = !empty($options['opt-telegram-active-spot-land']);

// Telegram credentials from options (fallback to empty strings)
if (!defined('SPOTPLAY_TG_TOKEN')) {
    define('SPOTPLAY_TG_TOKEN', isset($options['opt-telegram-token-spot-land']) ? (string) $options['opt-telegram-token-spot-land'] : ''); // توکن ربات تلگرام
}
if (!defined('SPOTPLAY_TG_ADMIN_CHAT_ID')) {
    define('SPOTPLAY_TG_ADMIN_CHAT_ID', isset($options['opt-telegram-admin-chat-id-spot-land']) ? (string) $options['opt-telegram-admin-chat-id-spot-land'] : ''); // آی‌دی چت ادمین
}
// توکن مخفی برای اعتبارسنجی وبهوک (اختیاری اما توصیه‌شده)
if (isset($options['opt-telegram-secret-spot-land']) && !defined('SPOTPLAY_TG_WEBHOOK_SECRET')) {
    define('SPOTPLAY_TG_WEBHOOK_SECRET', (string) $options['opt-telegram-secret-spot-land']);
}


function spl_tg_api_request($method, $body)
{
    // توکن را از کانستنت یا گزینه‌ها بخوانید تا همیشه در ادمین قابل استفاده باشد
    $token = defined('SPOTPLAY_TG_TOKEN') ? SPOTPLAY_TG_TOKEN : '';
    if (empty($token)) {
        $opts = get_option('spotplay_land');
        if (is_array($opts) && !empty($opts['opt-telegram-token-spot-land'])) {
            $token = (string) $opts['opt-telegram-token-spot-land'];
        }
    }
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

// نسخه دکمه‌های وبهوک (callback_data) برای استفاده در وبهوک تلگرام
function spl_tg_build_order_keyboard_callback($order_id)
{
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => 'تایید سفارش و ایجاد لایسنس', 'callback_data' => 'approve:' . $order_id],
            ],
            [
                ['text' => 'لغو سفارش', 'callback_data' => 'cancel:' . $order_id],
            ],
        ],
    ];
    return wp_json_encode($keyboard, JSON_UNESCAPED_UNICODE);
}

function spl_tg_send_order_notification($order_id)
{
    // اگر فعال نیست یا اطلاعات ناقص است، ارسال نکنید
    $chat_id = defined('SPOTPLAY_TG_ADMIN_CHAT_ID') ? SPOTPLAY_TG_ADMIN_CHAT_ID : '';
    if (empty($chat_id)) {
        $opts = get_option('spotplay_land');
        if (is_array($opts) && !empty($opts['opt-telegram-admin-chat-id-spot-land'])) {
            $chat_id = (string) $opts['opt-telegram-admin-chat-id-spot-land'];
        }
    }
    // بررسی وجود توکن با استفاده از تابع درخواست
    $has_token = spl_tg_api_request('getMe', []);
    if (!$has_token || empty($chat_id)) { return false; }
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
    // اگر وبهوک فعال باشد از callback_data استفاده کنید
    $reply_markup = spl_tg_build_order_keyboard_callback($order_id);

    if (!empty($photo_url)) {
        $body = [
            'chat_id'     => $chat_id,
            'photo'       => $photo_url,
            'caption'     => $caption,
            'parse_mode'  => 'HTML',
            'reply_markup'=> $reply_markup,
        ];
        return spl_tg_api_request('sendPhoto', $body);
    } else {
        $body = [
            'chat_id'     => $chat_id,
            'text'        => $caption,
            'parse_mode'  => 'HTML',
            'reply_markup'=> $reply_markup,
        ];
        return spl_tg_api_request('sendMessage', $body);
    }
}

// ثبت وبهوک در تلگرام
function spl_tg_set_webhook($custom_url = '')
{
    // دریافت توکن برای مدیریت وبهوک حتی وقتی غیرفعال است
    $token = defined('SPOTPLAY_TG_TOKEN') ? SPOTPLAY_TG_TOKEN : '';
    if (empty($token)) {
        $opts = get_option('spotplay_land');
        if (is_array($opts) && !empty($opts['opt-telegram-token-spot-land'])) {
            $token = (string) $opts['opt-telegram-token-spot-land'];
        }
    }
    if (empty($token)) { return false; }
    $url = $custom_url ?: admin_url('admin-ajax.php?action=spotplay_telegram_webhook');
    $body = [ 'url' => $url ];
    if (defined('SPOTPLAY_TG_WEBHOOK_SECRET') && !empty(SPOTPLAY_TG_WEBHOOK_SECRET)) {
        $body['secret_token'] = SPOTPLAY_TG_WEBHOOK_SECRET;
    } else {
        // تلاش برای خواندن از گزینه‌ها اگر کانستنت تعریف نشده بود
        $opts = get_option('spotplay_land');
        if (is_array($opts) && !empty($opts['opt-telegram-secret-spot-land'])) {
            $body['secret_token'] = (string) $opts['opt-telegram-secret-spot-land'];
        }
    }
    return spl_tg_api_request('setWebhook', $body);
}

// دریافت وبهوک و پردازش callback_data
function spl_tg_webhook_receiver()
{
    // تایید هدر secret اگر تنظیم شده باشد
    if (defined('SPOTPLAY_TG_WEBHOOK_SECRET') && !empty(SPOTPLAY_TG_WEBHOOK_SECRET)) {
        $hdr = isset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) ? $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
        if ($hdr !== SPOTPLAY_TG_WEBHOOK_SECRET) {
            status_header(403);
            wp_die('Forbidden');
        }
    }

    $raw = file_get_contents('php://input');
    $update = json_decode($raw, true);
    if (!is_array($update)) { status_header(400); wp_die('Bad Request'); }

    if (!empty($update['callback_query'])) {
        $cb = $update['callback_query'];
        $data = isset($cb['data']) ? (string) $cb['data'] : '';
        $id   = isset($cb['id']) ? (string) $cb['id'] : '';
        $from = isset($cb['from']) ? $cb['from'] : [];
        $message = isset($cb['message']) ? $cb['message'] : [];

        $parts = explode(':', $data);
        $op = isset($parts[0]) ? $parts[0] : '';
        $order_id = isset($parts[1]) ? absint($parts[1]) : 0;

        $text = '';
        if ($order_id && in_array($op, ['approve','cancel'], true)) {
            $order = wc_get_order($order_id);
            if ($order) {
                if ($op === 'approve') {
                    $order->set_status('completed');
                    $order->save();
                    if (function_exists('spl_handle_order_completed')) {
                        spl_handle_order_completed($order_id);
                    }
                    $text = 'سفارش تایید شد و لایسنس ایجاد شد.';
                } else {
                    $order->set_status('cancelled');
                    $order->save();
                    $text = 'سفارش لغو شد.';
                }
            } else {
                $text = 'سفارش یافت نشد.';
            }
        } else {
            $text = 'درخواست نامعتبر.';
        }

        // پاسخ به کلیک کاربر
        if (!empty($id)) {
            spl_tg_api_request('answerCallbackQuery', [
                'callback_query_id' => $id,
                'text' => $text,
                'show_alert' => false,
            ]);
        }

        // در صورت نیاز می‌توان کیبورد را حذف یا متن را به‌روزرسانی کرد
        if (!empty($message['chat']['id']) && !empty($message['message_id'])) {
            spl_tg_api_request('editMessageReplyMarkup', [
                'chat_id' => $message['chat']['id'],
                'message_id' => $message['message_id'],
                'reply_markup' => wp_json_encode(['inline_keyboard' => []]),
            ]);
        }
    }

    // پاسخ OK برای تلگرام
    wp_die('OK');
}
add_action('wp_ajax_nopriv_spotplay_telegram_webhook', 'spl_tg_webhook_receiver');
add_action('wp_ajax_spotplay_telegram_webhook', 'spl_tg_webhook_receiver');
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