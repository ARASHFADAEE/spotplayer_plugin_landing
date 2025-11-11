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
include_once LAND_PLUGIN_DIR."/freamwork/codestar-framework.php";
include_once LAND_PLUGIN_INC."/admin.php";

/**
 * Elementor widget registration (conditional by settings and plugin availability)
 */
function spotplayer_landing_register_elementor_widget( $widgets_manager ) {
    // Include widget class
    $widget_file = LAND_PLUGIN_DIR . 'elementor/class-spotplayer-landing-elementor-widget.php';
    if ( file_exists( $widget_file ) ) {
        include_once $widget_file;
        if ( class_exists( 'Spotplayer_Landing_Elementor_Widget' ) ) {
            $widgets_manager->register( new \Spotplayer_Landing_Elementor_Widget() );
        }
    }
}

// Register the widget only if Elementor is loaded and the option is enabled
add_action( 'plugins_loaded', function() {
    if ( did_action( 'elementor/loaded' ) ) {
        $csf_options = get_option( 'spotplay_land' );
        $enabled = is_array( $csf_options ) && ! empty( $csf_options['opt-elementor-spot-land'] );
        if ( $enabled ) {
            add_action( 'elementor/widgets/register', 'spotplayer_landing_register_elementor_widget' );
        }
    }
});


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
            'product_id' => spl_get_mapped_product_id(),
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


// گزارش خریداران اکنون داخل صفحه تنظیمات Codestar رندر می‌شود (inc/admin.php)






