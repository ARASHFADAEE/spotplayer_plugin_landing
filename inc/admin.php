<?php



// Check core class for avoid errors

use function PHPSTORM_META\type;

/**
 * Build dynamic options for active WooCommerce payment gateways
 *
 * @return array [gateway_id => gateway_title]
 */
if ( ! function_exists( 'spotplay_get_wc_gateways_options' ) ) {
    function spotplay_get_wc_gateways_options() {
        $options = array();

        if ( class_exists( 'WC_Payment_Gateways' ) ) {
            $gateways = WC_Payment_Gateways::instance()->get_available_payment_gateways();
            if ( is_array( $gateways ) && ! empty( $gateways ) ) {
                foreach ( $gateways as $gateway_id => $gateway_obj ) {
                    // Prefer human-readable title
                    $title = isset( $gateway_obj->title ) ? $gateway_obj->title : ( method_exists( $gateway_obj, 'get_title' ) ? $gateway_obj->get_title() : $gateway_id );
                    $options[ $gateway_id ] = $title;
                }
            }
        }

        return $options;
    }
}

if (class_exists('CSF')) {

    // Set a unique slug-like ID
    $prefix = 'spotplay_land';

    // Create options
    CSF::createOptions($prefix, array(
        'menu_title' => 'تنظیمات لنداسپات',
        'menu_slug'  => 'spotplayer-landing-setting',
    ));

    // Create a section
    CSF::createSection($prefix, array(
        'title'  => 'تنظیمات عمومی',
        'fields' => array(


            array(
                'id'    => 'opt-coupon-spot-land',
                'type'  => 'switcher',
                'title' => 'وضعیت کد تخفیف',

            ),


            array(
                'id'    => 'opt-elementor-spot-land',
                'type'  => 'switcher',
                'title' => 'سازگاری با المنتور',
            ),


        )
    ));


        // Create a section
    CSF::createSection($prefix, array(
        'title'  => 'تنظیمات تلگرام',
        'fields' => array(


           array(
                'id'    => 'opt-telegram-active-spot-land',
                'type'  => 'switcher',
                'title' => 'فعالسازی بات تلگرام',
            ),


            array(
                'id'    => 'opt-telegram-token-spot-land',
                'type'  => 'text',
                'title' => 'توکن بات تلگرام',

            ),


            array(
                'id'    => 'opt-telegram-admin-chat-id-spot-land',
                'type'  => 'text',
                'title' => 'چت ایدی ادمین',
            ),

            // Webhook secret (optional, recommended)
            array(
                'id'    => 'opt-telegram-secret-spot-land',
                'type'  => 'text',
                'title' => 'Webhook Secret (اختیاری)',
                'desc'  => 'یک مقدار امن برای اعتبارسنجی وبهوک تلگرام تنظیم کنید.',
                'attributes' => array('dir' => 'ltr'),
            ),

            // Admin tools: Set/Delete webhook buttons
            array(
                'id'       => 'opt-telegram-webhook-tools',
                'type'     => 'callback',
                'title'    => 'مدیریت وبهوک تلگرام',
                'function' => 'spotplay_render_telegram_webhook_tools',
            ),


        )
    ));

    /**
     * Create a section sms provider
     * 
     * @param string $prefix
     * @return void
     */
    CSF::createSection($prefix, array(
        'title'  => 'سامانه  پیامکی',
        'fields' => array(
            // انتخاب سامانه پیامک
            array(
                'id'          => 'opt-sms-provider',
                'type'        => 'select',
                'title'       => 'سامانه پیامک',
                'placeholder' => 'انتخاب سامانه',
                'chosen'      => true,
                'options'     => array(
                    'melipayamak' => 'ملی پیامک',
                    'kavenegar'   => 'کاوه‌نگار',
                ),
                'default'     => 'melipayamak',
            ),

            // فیلدهای ملی پیامک
            array(
                'id'         => 'opt-username-melipayamak',
                'type'       => 'text',
                'title'      => 'نام کاربری ملی پیامک',
                'dependency' => array('opt-sms-provider', '==', 'melipayamak'),
            ),
            array(
                'id'         => 'opt-password-melipayamak',
                'type'       => 'text',
                'title'      => 'رمز عبور ملی پیامک',
                'dependency' => array('opt-sms-provider', '==', 'melipayamak'),
            ),
            array(
                'id'         => 'opt-pattern-melipayamak',
                'type'       => 'text',
                'title'      => 'نام الگو/پترن ملی پیامک',
                'dependency' => array('opt-sms-provider', '==', 'melipayamak'),
            ),

            // فیلدهای کاوه‌نگار
            array(
                'id'         => 'opt-kavenegar-api_key',
                'type'       => 'text',
                'title'      => 'API کلید (کاوه‌نگار)',
                'dependency' => array('opt-sms-provider', '==', 'kavenegar'),
            ),
            array(
                'id'         => 'opt-kavenegar-pattern',
                'type'       => 'text',
                'title'      => 'نام الگو/پترن (کاوه‌نگار)',
                'dependency' => array('opt-sms-provider', '==', 'kavenegar'),
            ),


        )
    ));



    /**
     * create section Spotplayer setting
     * 
     */

    CSF::createSection($prefix, array(
        'title'  => 'تنظیمات اسپات پلیر',
        'fields' => array(
            // فیلدهای Spotplayer
            array(
                'id'         => 'opt-spotplayer-api_key',
                'type'       => 'text',
                'title'      => 'API کلید اسپات پلیر',
            ),
            array(
                'id'    => 'opt-spotplayer-level',
                'type'  => 'text',
                'title' => 'سطح (LEVEL$)',
            ),
            array(
                'id'     => 'opt-spotplayer-courses',
                'type'   => 'group',
                'title'  => 'شناسه دوره‌ها',
                'fields' => array(
                    array(
                        'id'    => 'course_id',
                        'type'  => 'text',
                        'title' => 'شناسه دوره',
                        'attributes' => array('dir' => 'ltr'),
                    ),
                ),
            ),
            array(
                'id'      => 'opt-spotplayer-test_mode',
                'type'    => 'switcher',
                'title'   => 'حالت لایسنس تستی',
                'default' => false,
            ),
        )
    ));



    /**
     * 
     * woocommerce setting
     * 
     */

    CSF::createSection($prefix,array(
        'title'  => 'تنظیمات ووکامرس',
        'fields' => array(
        // Product selector with AJAX search (stores selected product ID)
            array(
                'id'          => 'opt-product_id',
                'type'        => 'select',
                'title'       => 'انتخاب محصول',
                'placeholder' => 'جستجوی محصول...',
                'chosen'      => true,
                'ajax'        => true,
                'options'     => 'posts',
                'query_args'  => array(
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => 25,
                ),
            ),

            


            array(
                'id'    => 'opt-CardByCard-spot-land',
                'type'  => 'switcher',
                'title' => 'سیستم کارت به کارت',
                'default' => false,
            ),

            // فیلدهای کارت به کارت: نمایش فقط در صورت فعال بودن سوئیچر
            array(
                'id'         => 'opt-card_number',
                'type'       => 'text',
                'title'      => 'شماره کارت',
                'placeholder'=> 'مثلاً 6037-...-....-....',
                'attributes' => array('dir' => 'ltr'),
                'dependency' => array('opt-CardByCard-spot-land', '==', 'true'),
            ),
            array(
                'id'         => 'opt-card_holder',
                'type'       => 'text',
                'title'      => 'نام صاحب کارت',
                'dependency' => array('opt-CardByCard-spot-land', '==', 'true'),
            ),

            // استفاده از درگاه‌های ووکامرس (داینامیک)
            array(
                'id'      => 'opt-wc-gateways-enable',
                'type'    => 'switcher',
                'title'   => 'استفاده از درگاه‌های ووکامرس',
                'default' => false,
            ),
            array(
                'id'           => 'opt-wc-gateways',
                'type'         => 'checkbox',
                'title'        => 'انتخاب درگاه‌های فعال',
                'dependency'   => array( 'opt-wc-gateways-enable', '==', 'true' ),
                'options'      => spotplay_get_wc_gateways_options(),
                'check_all'    => true,
                'empty_message'=> 'درگاه فعالی یافت نشد یا ووکامرس غیرفعال است.',
            ),

        )
    ));

    

    // Buyer Report section embedded inside Codestar settings
    CSF::createSection($prefix, array(
        'title'  => 'گزارش خریداران',
        'fields' => array(
            array(
                'id'       => 'opt-buyer-report',
                'type'     => 'callback',
                'function' => 'spotplay_render_buyer_report_field',
            ),
        ),
    ));
}

/**
 * Render Buyer Report inside Codestar settings via callback field.
 * Supports search by name or phone and simple pagination.
 *
 * @param array $field
 * @param mixed $value
 * @return void
 */
function spotplay_render_buyer_report_field($args = null)
{
    if (!current_user_can('manage_options')) {
        echo '<p>' . esc_html(__('شما دسترسی کافی برای مشاهده این بخش ندارید.', 'spotplayer-landing')) . '</p>';
        return;
    }

    // Read query params
    $search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $search_digits = $search !== '' ? preg_replace('/\D+/', '', $search) : '';
    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 25;

    // Fetch recent orders and filter by mapped product
    $mapped_product_id = function_exists('spl_get_mapped_product_id') ? spl_get_mapped_product_id() : 0;
    $args = array(
        'limit'   => 250,
        'orderby' => 'date',
        'order'   => 'DESC',
        'status'  => array_keys(wc_get_order_statuses()),
    );
    $orders = function_exists('wc_get_orders') ? wc_get_orders($args) : array();

    $filtered = array();
    foreach ($orders as $order) {
        if (!$order instanceof WC_Order) { continue; }

        // Only show orders containing mapped product (if set)
        $show = true;
        if ($mapped_product_id) {
            $show = false;
            foreach ($order->get_items('line_item') as $item_id => $item) {
                $pid = 0; $vid = 0;
                if (is_object($item) && method_exists($item, 'get_data')) {
                    $data = (array) $item->get_data();
                    $pid = isset($data['product_id']) ? (int)$data['product_id'] : 0;
                    $vid = isset($data['variation_id']) ? (int)$data['variation_id'] : 0;
                } else {
                    if (function_exists('wc_get_order_item_meta')) {
                        $pid = (int) wc_get_order_item_meta($item_id, '_product_id', true);
                        $vid = (int) wc_get_order_item_meta($item_id, '_variation_id', true);
                    }
                }
                if ($pid === $mapped_product_id) { $show = true; break; }
                if ($vid) {
                    $parent_id = (int) wp_get_post_parent_id($vid);
                    if ($parent_id === $mapped_product_id) { $show = true; break; }
                }
            }
        }
        if (!$show) { continue; }

        // Apply search filter if provided
        if ($search !== '') {
            $name = trim($order->get_formatted_billing_full_name());
            $order_phone = preg_replace('/\D+/', '', (string) $order->get_billing_phone());
            $match = false;
            if ($search_digits !== '' && strlen($search_digits) >= 4) {
                $match = (strpos($order_phone, $search_digits) !== false);
            } else {
                $match = (stripos($name, $search) !== false);
            }
            if (!$match) { continue; }
        }

        $filtered[] = $order;
    }

    $total = count($filtered);
    $total_pages = max(1, (int) ceil($total / $per_page));
    if ($paged > $total_pages) { $paged = $total_pages; }
    $offset = ($paged - 1) * $per_page;
    $page_orders = array_slice($filtered, $offset, $per_page);

    // Container and AJAX search form
    $nonce = wp_create_nonce('spotplay_buyer_report');
    echo '<div id="spotplay-buyer-report">';
    // Search bar (no inner form to avoid nested form submission)
    echo '<div id="spotplay-buyer-search" style="margin:10px 0">';
    echo '<input type="hidden" name="nonce" value="' . esc_attr($nonce) . '" />';
    echo '<input type="text" name="q" value="' . esc_attr($search) . '" placeholder="' . esc_attr(__('جستجو نام یا شماره موبایل', 'spotplayer-landing')) . '" style="min-width:280px" /> ';
    echo '<button type="button" id="spotplay-buyer-search-btn" class="button">' . esc_html(__('جستجو', 'spotplayer-landing')) . '</button>';
    echo '</div>';

    echo '<div class="results">';
    // Results table
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>'
        . '<th>' . esc_html(__('شناسه', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('نام مشتری', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('شماره موبایل', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('وضعیت', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('کلید لایسنس', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('تاریخ', 'spotplayer-landing')) . '</th>'
        . '</tr></thead><tbody>';

    foreach ($page_orders as $order) {
        $oid = $order->get_id();
        $name = trim($order->get_formatted_billing_full_name());
        $phone = (string) $order->get_billing_phone();
        $status = wc_get_order_status_name($order->get_status());
        $license_key = (string) $order->get_meta('_spotplayer_license_key');
        $date = esc_html($order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i') : '');

        echo '<tr>'
            . '<td>#' . esc_html($oid) . '</td>'
            . '<td>' . esc_html($name) . '</td>'
            . '<td>' . esc_html($phone) . '</td>'
            . '<td>' . esc_html($status) . '</td>'
            . '<td>' . ($license_key
                ? '<button type="button" class="button copy-license-btn" data-license="' . esc_attr($license_key) . '">' . esc_html(__('کپی لایسنس', 'spotplayer-landing')) . '</button>'
                : '<em>' . esc_html(__('— ندارد —', 'spotplayer-landing')) . '</em>')
            . '</td>'
            . '<td>' . $date . '</td>'
            . '</tr>';
    }

    if (empty($page_orders)) {
        echo '<tr><td colspan="7"><em>' . esc_html(__('موردی یافت نشد.', 'spotplayer-landing')) . '</em></td></tr>';
    }

    echo '</tbody></table>';

    // Pagination controls (AJAX)
    echo '<div class="pagination" style="margin-top:10px; display:flex; align-items:center; gap:10px">';
    echo '<span>' . sprintf(esc_html(__('صفحه %1$d از %2$d', 'spotplayer-landing')), (int)$paged, (int)$total_pages) . '</span>';
    if ($paged > 1) {
        echo '<a class="button" href="#" data-page="' . (int)($paged - 1) . '">' . esc_html(__('قبلی', 'spotplayer-landing')) . '</a>';
    } else {
        echo '<span class="button disabled" style="opacity:.6">' . esc_html(__('قبلی', 'spotplayer-landing')) . '</span>';
    }
    if ($paged < $total_pages) {
        echo '<a class="button" href="#" data-page="' . (int)($paged + 1) . '">' . esc_html(__('بعدی', 'spotplayer-landing')) . '</a>';
    } else {
        echo '<span class="button disabled" style="opacity:.6">' . esc_html(__('بعدی', 'spotplayer-landing')) . '</span>';
    }
    echo '</div>';

    echo '</div>'; // .results

    echo '</div>'; // #spotplay-buyer-report
}

// Enqueue admin script for AJAX report interactions on our settings page
function spotplay_enqueue_admin_report_scripts($hook_suffix) {
    if (!is_admin()) { return; }
    $is_our_page = isset($_GET['page']) && $_GET['page'] === 'spotplayer-landing-setting';
    if (!$is_our_page) { return; }
    wp_enqueue_script(
        'spotplay-admin-buyer-report',
        LAND_PLUGIN_ASSETS_URL . 'js/admin-buyer-report.js',
        array('jquery'),
        '1.0.0',
        true
    );
    wp_localize_script('spotplay-admin-buyer-report', 'spotplayBuyerReport', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}
add_action('admin_enqueue_scripts', 'spotplay_enqueue_admin_report_scripts');

/**
 * AJAX handler: render Buyer Report table and pagination only
 */
function spotplay_buyer_report_ajax() {
    if (!current_user_can('manage_options')) { wp_die(__('عدم دسترسی', 'spotplayer-landing')); }
    $nonce = isset($_REQUEST['nonce']) ? (string) $_REQUEST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'spotplay_buyer_report')) { wp_die(__('درخواست نامعتبر', 'spotplayer-landing')); }

    $search = isset($_REQUEST['q']) ? sanitize_text_field(wp_unslash($_REQUEST['q'])) : '';
    $search_digits = $search !== '' ? preg_replace('/\D+/', '', $search) : '';
    $paged = isset($_REQUEST['paged']) ? max(1, absint($_REQUEST['paged'])) : 1;
    $per_page = 25;

    $mapped_product_id = function_exists('spl_get_mapped_product_id') ? spl_get_mapped_product_id() : 0;
    $args = array(
        'limit'   => 250,
        'orderby' => 'date',
        'order'   => 'DESC',
        'status'  => array_keys(wc_get_order_statuses()),
    );
    $orders = function_exists('wc_get_orders') ? wc_get_orders($args) : array();

    $filtered = array();
    foreach ($orders as $order) {
        if (!$order instanceof WC_Order) { continue; }
        $show = true;
        if ($mapped_product_id) {
            $show = false;
            foreach ($order->get_items('line_item') as $item_id => $item) {
                $pid = 0; $vid = 0;
                if (is_object($item) && method_exists($item, 'get_data')) {
                    $data = (array) $item->get_data();
                    $pid = isset($data['product_id']) ? (int)$data['product_id'] : 0;
                    $vid = isset($data['variation_id']) ? (int)$data['variation_id'] : 0;
                }
                if ($pid === $mapped_product_id) { $show = true; break; }
                if ($vid) {
                    $parent_id = (int) wp_get_post_parent_id($vid);
                    if ($parent_id === $mapped_product_id) { $show = true; break; }
                }
            }
        }
        if (!$show) { continue; }

        if ($search !== '') {
            $name = trim($order->get_formatted_billing_full_name());
            $order_phone = preg_replace('/\D+/', '', (string) $order->get_billing_phone());
            $match = false;
            if ($search_digits !== '' && strlen($search_digits) >= 4) {
                $match = (strpos($order_phone, $search_digits) !== false);
            } else {
                $match = (stripos($name, $search) !== false);
            }
            if (!$match) { continue; }
        }
        $filtered[] = $order;
    }

    $total = count($filtered);
    $total_pages = max(1, (int) ceil($total / $per_page));
    if ($paged > $total_pages) { $paged = $total_pages; }
    $offset = ($paged - 1) * $per_page;
    $page_orders = array_slice($filtered, $offset, $per_page);

    // Table
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>'
        . '<th>' . esc_html(__('شناسه سفارش', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('نام مشتری', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('شماره موبایل', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('وضعیت', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('کلید لایسنس', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('تاریخ', 'spotplayer-landing')) . '</th>'
        . '</tr></thead><tbody>';

    foreach ($page_orders as $order) {
        $oid = $order->get_id();
        $name = trim($order->get_formatted_billing_full_name());
        $phone = (string) $order->get_billing_phone();
        $status = wc_get_order_status_name($order->get_status());
        $license_key = (string) $order->get_meta('_spotplayer_license_key');
        $date = esc_html($order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i') : '');

        echo '<tr>'
            . '<td>#' . esc_html($oid) . '</td>'
            . '<td>' . esc_html($name) . '</td>'
            . '<td>' . esc_html($phone) . '</td>'
            . '<td>' . esc_html($status) . '</td>'
            . '<td>' . ($license_key
                ? '<button type="button" class="button copy-license-btn" data-license="' . esc_attr($license_key) . '">' . esc_html(__('کپی لایسنس', 'spotplayer-landing')) . '</button>'
                : '<em>' . esc_html(__('— ندارد —', 'spotplayer-landing')) . '</em>')
            . '</td>'
            . '<td>' . $date . '</td>'
            . '</tr>';
    }

    if (empty($page_orders)) {
        echo '<tr><td colspan="7"><em>' . esc_html(__('موردی یافت نشد.', 'spotplayer-landing')) . '</em></td></tr>';
    }

    echo '</tbody></table>';

    // Pagination
    echo '<div class="pagination" style="margin-top:10px; display:flex; align-items:center; gap:10px">';
    echo '<span>' . sprintf(esc_html(__('صفحه %1$d از %2$d', 'spotplayer-landing')), (int)$paged, (int)$total_pages) . '</span>';
    if ($paged > 1) {
        echo '<a class="button" href="#" data-page="' . (int)($paged - 1) . '">' . esc_html(__('قبلی', 'spotplayer-landing')) . '</a>';
    } else {
        echo '<span class="button disabled" style="opacity:.6">' . esc_html(__('قبلی', 'spotplayer-landing')) . '</span>';
    }
    if ($paged < $total_pages) {
        echo '<a class="button" href="#" data-page="' . (int)($paged + 1) . '">' . esc_html(__('بعدی', 'spotplayer-landing')) . '</a>';
    } else {
        echo '<span class="button disabled" style="opacity:.6">' . esc_html(__('بعدی', 'spotplayer-landing')) . '</span>';
    }
    echo '</div>';

    wp_die();
}
add_action('wp_ajax_spotplay_buyer_report_search', 'spotplay_buyer_report_ajax');

/**
 * Render Telegram webhook tools (Set/Delete buttons) inside Codestar settings
 */
function spotplay_render_telegram_webhook_tools($args = null) {
    if (!current_user_can('manage_options')) {
        echo '<p>' . esc_html(__('شما دسترسی کافی برای این بخش ندارید.', 'spotplayer-landing')) . '</p>';
        return;
    }
    $nonce = wp_create_nonce('spotplay_tg_admin_webhook');
    $webhook_url = admin_url('admin-ajax.php?action=spotplay_telegram_webhook');

    echo '<div class="spotplay-tg-webhook-tools">';
    echo '<p><strong>' . esc_html(__('آدرس وبهوک', 'spotplayer-landing')) . ':</strong> ' . esc_html($webhook_url) . '</p>';

    // نمایش وضعیت وبهوک فعلی از تلگرام (در صورت وجود تابع)
    if (function_exists('spl_tg_api_request')) {
        $info = spl_tg_api_request('getWebhookInfo', array());
        if (is_array($info) && !empty($info['ok']) && !empty($info['result'])) {
            $res = $info['result'];
            $url = isset($res['url']) ? (string) $res['url'] : '';
            $pending = isset($res['pending_update_count']) ? (int) $res['pending_update_count'] : 0;
            $last_err = isset($res['last_error_message']) ? (string) $res['last_error_message'] : '';
            echo '<ul style="margin:8px 0">';
            echo '<li><strong>URL:</strong> ' . esc_html($url) . '</li>';
            echo '<li><strong>Pending:</strong> ' . esc_html((string) $pending) . '</li>';
            if ($last_err) { echo '<li><strong>Error:</strong> ' . esc_html($last_err) . '</li>'; }
            echo '</ul>';
        }
    }

    echo '<div class="buttons" style="display:flex; gap:10px; margin-top:10px">';
    echo '<button type="button" class="button button-primary" id="spotplay-tg-set-webhook">' . esc_html(__('ثبت وبهوک', 'spotplayer-landing')) . '</button>';
    echo '<button type="button" class="button" id="spotplay-tg-delete-webhook">' . esc_html(__('حذف وبهوک', 'spotplayer-landing')) . '</button>';
    echo '</div>';
    echo '<div id="spotplay-tg-webhook-result" style="margin-top:10px"></div>';
    echo '<input type="hidden" id="spotplay-tg-webhook-nonce" value="' . esc_attr($nonce) . '" />';

    echo '<script>(function($){
        function showResult(msg, ok){
            var el = $("#spotplay-tg-webhook-result");
            el.text(msg).css({color: ok ? "#008000" : "#cc0000"});
        }
        $("#spotplay-tg-set-webhook").on("click", function(){
            var nonce = $("#spotplay-tg-webhook-nonce").val();
            $.post(ajaxurl, {action: "spotplay_tg_admin_set_webhook", nonce: nonce}, function(resp){
                if(resp && resp.success){ showResult(resp.data.message || "وبهوک ثبت شد.", true); }
                else { showResult((resp && resp.data && resp.data.message) || "خطا در ثبت وبهوک.", false); }
            });
        });
        $("#spotplay-tg-delete-webhook").on("click", function(){
            var nonce = $("#spotplay-tg-webhook-nonce").val();
            $.post(ajaxurl, {action: "spotplay_tg_admin_delete_webhook", nonce: nonce}, function(resp){
                if(resp && resp.success){ showResult(resp.data.message || "وبهوک حذف شد.", true); }
                else { showResult((resp && resp.data && resp.data.message) || "خطا در حذف وبهوک.", false); }
            });
        });
    })(jQuery);</script>';

    echo '</div>';
}

// AJAX: Set webhook
function spotplay_tg_admin_set_webhook_ajax(){
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => __('عدم دسترسی', 'spotplayer-landing')]); }
    $nonce = isset($_POST['nonce']) ? (string) $_POST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'spotplay_tg_admin_webhook')) { wp_send_json_error(['message' => __('درخواست نامعتبر', 'spotplayer-landing')]); }
    $url = admin_url('admin-ajax.php?action=spotplay_telegram_webhook');
    if (function_exists('spl_tg_set_webhook')) {
        $ok = spl_tg_set_webhook($url);
        if (is_array($ok) && !empty($ok['ok'])) {
            wp_send_json_success(['message' => __('وبهوک با موفقیت ثبت شد.', 'spotplayer-landing')]);
        }
    }
    wp_send_json_error(['message' => __('ثبت وبهوک با خطا مواجه شد.', 'spotplayer-landing')]);
}
add_action('wp_ajax_spotplay_tg_admin_set_webhook', 'spotplay_tg_admin_set_webhook_ajax');

// AJAX: Delete webhook
function spotplay_tg_admin_delete_webhook_ajax(){
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => __('عدم دسترسی', 'spotplayer-landing')]); }
    $nonce = isset($_POST['nonce']) ? (string) $_POST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'spotplay_tg_admin_webhook')) { wp_send_json_error(['message' => __('درخواست نامعتبر', 'spotplayer-landing')]); }
    if (function_exists('spl_tg_api_request')) {
        $ok = spl_tg_api_request('deleteWebhook', array());
        if (is_array($ok) && !empty($ok['ok'])) {
            wp_send_json_success(['message' => __('وبهوک حذف شد.', 'spotplayer-landing')]);
        }
    }
    wp_send_json_error(['message' => __('حذف وبهوک با خطا مواجه شد.', 'spotplayer-landing')]);
}
add_action('wp_ajax_spotplay_tg_admin_delete_webhook', 'spotplay_tg_admin_delete_webhook_ajax');
