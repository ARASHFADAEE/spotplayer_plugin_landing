<?php

/*
 * Plugin Name:      پلاگین فروش دوره سریع در اسپات پلیر
 * Plugin URI:        https://fadaee.dev
 * Description:       خیلی سریع با این پلاگین میتونی دورت رو بهش متصل کنی و هم با کارت به کارت و هم با درگاه پرداخت آنلاین ازش استفاده کنی
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Arash fadaee
 * Author URI:        https://Fadaee.dev
 * License:           GPL v2 or later
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       spotplayer-landing
 * Requires Plugins:  woocommerce
 */


if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


/**
 * define data base url and dir
 * 
 * @return void
 */

define('LAND_PLUGIN_URL', plugin_dir_url(__FILE__));

define('LAND_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('LAND_PLUGIN_ASSETS_URL', LAND_PLUGIN_URL . 'assets/');

define('LAND_PLUGIN_INC', LAND_PLUGIN_DIR . 'inc/');


$options = get_option( 'spotplay_land' ); 



/**
 * include denpedenci
 * 
 * @return void
 */

include_once LAND_PLUGIN_INC."/card-by-card.php";
include_once LAND_PLUGIN_INC."/spotplayer.php";


/**
 * include codestar framework
 * 
 * @return void
 */
include_once LAND_PLUGIN_DIR."/codestar/codestar-framework.php";
include_once LAND_PLUGIN_INC."/admin.php";


/**
 * 
 * set option meta
 * 
 * @return void
 */

function data_meta_option(){
    update_option('product_id',213);
    update_option('color-btn','green');
    update_option('is_copon',0);
    update_option('is_zibal',0);
    update_option('is_card',0);
    // SpotPlayer defaults
    update_option('spotplayer_api_key', '');
    update_option('spotplayer_level', '-1');
    update_option('spotplayer_courses', '');
    update_option('spotplayer_test_mode', 0);
    // Card-to-card defaults
    update_option('card_number', '');
    update_option('card_holder', '');
    // Meli Payamak defaults
    update_option('meli_username', '');
    update_option('meli_password', '');
    update_option('meli_body_id', '');
}


/**
 * register_activation_hook for plugin
 * 
 * @return void
 */
register_activation_hook(
	__FILE__,
	'data_meta_option'
);



/**
 * 
 * enqueue style and script
 * 
 * @return void
 */


function spotplayer_landing_enqueue_scripts()
{
        wp_enqueue_style('spotplayer-landing-style', LAND_PLUGIN_ASSETS_URL . 'css/style.css');
        wp_enqueue_script('spotplayer-landing-script', LAND_PLUGIN_ASSETS_URL . 'js/main.js', array('jquery'), '1.0.0', true);

        
        /**
         * 
         * localize script
         * 
         * @return void
         */

        wp_localize_script('spotplayer-landing-script', 'spotplayerLanding', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('spotplayer_landing_nonce'),
            'product_id' => (int)get_option('product_id'),
            'is_zibal'   => (int)get_option('is_zibal'),
            'is_card'    => (int)get_option('is_card'),
            'card_number'=> (string)get_option('card_number'),
            'card_holder'=> (string)get_option('card_holder'),
        ));
    
}
add_action('wp_enqueue_scripts', 'spotplayer_landing_enqueue_scripts');


/**
 * 
 * shortcode
 * 
 * @return void
 */


function land_box()
{

    global $options;
    
    include LAND_PLUGIN_INC . 'shortcode.php';
}

add_shortcode('spotplayer_landing', 'land_box');


/**
 * Admin settings: add an options page to configure plugin.
 */
function spotplayer_landing_add_admin_menu()
{
    add_menu_page(
        __('اسپات پلیر لندینگ', 'spotplayer-landing'),
        __('اسپات پلیر لندینگ', 'spotplayer-landing'),
        'manage_options',
        'spotplayer-landing',
        'spotplayer_landing_render_settings_page',
        'dashicons-welcome-learn-more',
        58
    );

    add_submenu_page(
        'spotplayer-landing',
        __('تنظیمات', 'spotplayer-landing'),
        __('تنظیمات', 'spotplayer-landing'),
        'manage_options',
        'spotplayer-landing',
        'spotplayer_landing_render_settings_page'
    );

    add_submenu_page(
        'spotplayer-landing',
        __('گزارش خریداران', 'spotplayer-landing'),
        __('گزارش خریداران', 'spotplayer-landing'),
        'manage_options',
        'spotplayer-landing-report',
        'spotplayer_landing_render_report_page'
    );
}
add_action('admin_menu', 'spotplayer_landing_add_admin_menu');

function spotplayer_landing_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['spotplayer_landing_settings_submit'])) {
        check_admin_referer('spotplayer_landing_settings');
        // Save options
        update_option('product_id', isset($_POST['product_id']) ? absint($_POST['product_id']) : 0);
        update_option('is_copon', isset($_POST['is_copon']) ? 1 : 0);
        update_option('is_zibal', isset($_POST['is_zibal']) ? 1 : 0);
        update_option('is_card', isset($_POST['is_card']) ? 1 : 0);
        update_option('card_number', isset($_POST['card_number']) ? sanitize_text_field($_POST['card_number']) : '');
        update_option('card_holder', isset($_POST['card_holder']) ? sanitize_text_field($_POST['card_holder']) : '');
        update_option('spotplayer_api_key', isset($_POST['spotplayer_api_key']) ? sanitize_text_field($_POST['spotplayer_api_key']) : '');
        update_option('spotplayer_level', isset($_POST['spotplayer_level']) ? sanitize_text_field($_POST['spotplayer_level']) : '-1');
        update_option('spotplayer_courses', isset($_POST['spotplayer_courses']) ? sanitize_textarea_field($_POST['spotplayer_courses']) : '');
        update_option('spotplayer_test_mode', isset($_POST['spotplayer_test_mode']) ? 1 : 0);
        // Meli Payamak settings
        update_option('meli_username', isset($_POST['meli_username']) ? sanitize_text_field($_POST['meli_username']) : '');
        update_option('meli_password', isset($_POST['meli_password']) ? sanitize_text_field($_POST['meli_password']) : '');
        update_option('meli_body_id', isset($_POST['meli_body_id']) ? sanitize_text_field($_POST['meli_body_id']) : '');
        echo '<div class="updated"><p>' . esc_html__('تنظیمات ذخیره شد.', 'spotplayer-landing') . '</p></div>';
    }

    // Fetch WooCommerce products for selection
    $products = [];
    if (class_exists('WC_Product')) {
        $products = function_exists('wc_get_products') ? wc_get_products(['status' => 'publish', 'limit' => 200]) : [];
    }

    // Active gateways preview
    $gateways_list = [];
    if (function_exists('WC')) {
        $pgw = WC()->payment_gateways();
        if ($pgw && method_exists($pgw, 'get_available_payment_gateways')) {
            $available = $pgw->get_available_payment_gateways();
            foreach ($available as $id => $gw) {
                $gateways_list[] = $gw->get_title() . ' (' . $id . ')';
            }
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(__('تنظیمات لندینگ اسپات پلیر', 'spotplayer-landing')); ?></h1>
        <form method="post">
            <?php wp_nonce_field('spotplayer_landing_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="product_id"><?php echo esc_html(__('انتخاب محصول ووکامرس', 'spotplayer-landing')); ?></label></th>
                    <td>
                        <select id="product_id" name="product_id">
                            <option value="0"><?php echo esc_html(__('— انتخاب کنید —', 'spotplayer-landing')); ?></option>
                            <?php
                            $current_pid = (int) get_option('product_id');
                            if (!empty($products)) {
                                foreach ($products as $p) {
                                    $pid = (int) $p->get_id();
                                    $title = $p->get_name();
                                    echo '<option value="' . esc_attr($pid) . '"' . selected($current_pid, $pid, false) . '>' . esc_html($title) . ' (ID: ' . esc_html($pid) . ')</option>';
                                }
                            }
                            ?>
                        </select>
                        <p class="description"><?php echo esc_html(__('شناسه محصول به‌صورت خودکار ذخیره می‌شود.', 'spotplayer-landing')); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('فعال‌سازی کارت به کارت', 'spotplayer-landing')); ?></th>
                    <td><label><input type="checkbox" name="is_card" <?php checked(1, (int)get_option('is_card')); ?> /> <?php echo esc_html(__('فعال', 'spotplayer-landing')); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('شماره کارت', 'spotplayer-landing')); ?></th>
                    <td><input type="text" name="card_number" value="<?php echo esc_attr(get_option('card_number')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('نام صاحب کارت', 'spotplayer-landing')); ?></th>
                    <td><input type="text" name="card_holder" value="<?php echo esc_attr(get_option('card_holder')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('فعال‌سازی درگاه (زیبال)', 'spotplayer-landing')); ?></th>
                    <td><label><input type="checkbox" name="is_zibal" <?php checked(1, (int)get_option('is_zibal')); ?> /> <?php echo esc_html(__('فعال', 'spotplayer-landing')); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('درگاه‌های فعال ووکامرس', 'spotplayer-landing')); ?></th>
                    <td>
                        <?php if (!empty($gateways_list)) { echo '<ul style="margin:0">'; foreach ($gateways_list as $g) { echo '<li>' . esc_html($g) . '</li>'; } echo '</ul>'; } else { echo '<em>' . esc_html(__('درگاه فعالی یافت نشد یا ووکامرس در دسترس نیست.', 'spotplayer-landing')) . '</em>'; } ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('فعالسازی کد تخفیف', 'spotplayer-landing')); ?></th>
                    <td><label><input type="checkbox" name="is_copon" <?php checked(1, (int)get_option('is_copon')); ?> /> <?php echo esc_html(__('فعال', 'spotplayer-landing')); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="spotplayer_api_key"><?php echo esc_html(__('کلید API اسپات پلیر', 'spotplayer-landing')); ?></label></th>
                    <td><input type="text" id="spotplayer_api_key" name="spotplayer_api_key" value="<?php echo esc_attr(get_option('spotplayer_api_key')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="spotplayer_level"><?php echo esc_html(__('سطح (LEVEL$)', 'spotplayer-landing')); ?></label></th>
                    <td><input type="text" id="spotplayer_level" name="spotplayer_level" value="<?php echo esc_attr(get_option('spotplayer_level', '-1')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="spotplayer_courses"><?php echo esc_html(__('شناسه دوره‌ها (با کاما یا خط‌جدید جدا کنید)', 'spotplayer-landing')); ?></label></th>
                    <td><textarea id="spotplayer_courses" name="spotplayer_courses" rows="4" cols="60"><?php echo esc_textarea(get_option('spotplayer_courses')); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(__('مد تست لایسنس', 'spotplayer-landing')); ?></th>
                    <td><label><input type="checkbox" name="spotplayer_test_mode" <?php checked(1, (int)get_option('spotplayer_test_mode')); ?> /> <?php echo esc_html(__('فعال', 'spotplayer-landing')); ?></label></td>
                </tr>
                <tr>
                    <th colspan="2"><h2><?php echo esc_html(__('ارسال پیامک (ملی پیامک)', 'spotplayer-landing')); ?></h2></th>
                </tr>
                <tr>
                    <th scope="row"><label for="meli_username"><?php echo esc_html(__('نام کاربری ملی پیامک', 'spotplayer-landing')); ?></label></th>
                    <td><input type="text" id="meli_username" name="meli_username" value="<?php echo esc_attr(get_option('meli_username')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="meli_password"><?php echo esc_html(__('رمز عبور ملی پیامک', 'spotplayer-landing')); ?></label></th>
                    <td><input type="text" id="meli_password" name="meli_password" value="<?php echo esc_attr(get_option('meli_password')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="meli_body_id"><?php echo esc_html(__('کد پترن (bodyId)', 'spotplayer-landing')); ?></label></th>
                    <td>
                        <input type="text" id="meli_body_id" name="meli_body_id" value="<?php echo esc_attr(get_option('meli_body_id')); ?>" />
                        <p class="description"><?php echo esc_html(__('متن ارسالی به صورت @bodyId@arg1 خواهد بود؛ arg1 همان کلید لایسنس است.', 'spotplayer-landing')); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary" name="spotplayer_landing_settings_submit" value="1"><?php echo esc_html(__('ذخیره تنظیمات', 'spotplayer-landing')); ?></button>
            </p>
        </form>
    </div>
    <?php
}


/**
 * Render report page listing buyers and license keys.
 */
function spotplayer_landing_render_report_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $mapped_product_id = (int) get_option('product_id');
    $args = [
        'limit' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => array_keys(wc_get_order_statuses()),
    ];
    $orders = function_exists('wc_get_orders') ? wc_get_orders($args) : [];

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(__('گزارش خریداران و لایسنس‌ها', 'spotplayer-landing')) . '</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>'
        . '<th>' . esc_html(__('شناسه سفارش', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('نام مشتری', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('وضعیت', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('کلید لایسنس', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('لینک لایسنس', 'spotplayer-landing')) . '</th>'
        . '<th>' . esc_html(__('تاریخ', 'spotplayer-landing')) . '</th>'
        . '</tr></thead><tbody>';

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
                }
                if ($pid === $mapped_product_id) { $show = true; break; }
                if ($vid) {
                    $parent_id = (int) wp_get_post_parent_id($vid);
                    if ($parent_id === $mapped_product_id) { $show = true; break; }
                }
            }
        }
        if (!$show) { continue; }

        $oid = $order->get_id();
        $name = trim($order->get_formatted_billing_full_name());
        $status = wc_get_order_status_name($order->get_status());
        $license_key = (string) $order->get_meta('_spotplayer_license_key');
        $license_url = (string) $order->get_meta('_spotplayer_license_url');
        $date = esc_html($order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i') : '');

        echo '<tr>'
            . '<td>#' . esc_html($oid) . '</td>'
            . '<td>' . esc_html($name) . '</td>'
            . '<td>' . esc_html($status) . '</td>'
            . '<td>' . ($license_key ? esc_html($license_key) : '<em>' . esc_html(__('— ندارد —', 'spotplayer-landing')) . '</em>') . '</td>'
            . '<td>' . ($license_url ? '<a href="' . esc_url($license_url) . '" target="_blank">' . esc_html(__('مشاهده', 'spotplayer-landing')) . '</a>' : '<em>' . esc_html(__('— ندارد —', 'spotplayer-landing')) . '</em>') . '</td>'
            . '<td>' . $date . '</td>'
            . '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}






