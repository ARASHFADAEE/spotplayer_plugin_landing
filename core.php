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


/**
 * 
 * set option meta
 * 
 * @return void
 */

function data_meta_option(){
    update_option('product_id','');
    update_option('color-btn','green');
    update_option('is_copon',0);
    update_option('zibal',0);


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
            'ajax_url' => admin_url('admin-ajax.php'),
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
    
    include LAND_PLUGIN_INC . 'shortcode.php';
}

add_shortcode('spotplayer_landing', 'land_box');



