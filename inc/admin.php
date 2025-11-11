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
}
