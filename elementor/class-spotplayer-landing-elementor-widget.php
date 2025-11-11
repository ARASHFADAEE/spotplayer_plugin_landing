<?php
// Elementor widget for SpotPlayer Landing box

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( class_exists( '\\Elementor\\Widget_Base' ) ) {

    class Spotplayer_Landing_Elementor_Widget extends \Elementor\Widget_Base {

        public function get_name() {
            return 'spotplayer_landing_widget';
        }

        public function get_title() {
            return __( 'باکس لندینگ اسپات پلیر', 'spotplayer-landing' );
        }

        public function get_icon() {
            return 'eicon-button';
        }

        public function get_categories() {
            return [ 'general' ];
        }

        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __( 'تنظیمات محتوا', 'spotplayer-landing' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'product_short_desc',
                [
                    'label' => __( 'متن کوتاه محصول (Override)', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::TEXTAREA,
                    'rows'  => 4,
                    'default' => '',
                    'description' => __( 'در صورت پرشدن، متن توضیحات کوتاه محصول با این متن جایگزین می‌شود.', 'spotplayer-landing' ),
                ]
            );

            $this->add_control(
                'use_custom_product_text',
                [
                    'label' => __( 'استفاده از متن دلخواه محصول', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'بله', 'spotplayer-landing' ),
                    'label_off' => __( 'خیر', 'spotplayer-landing' ),
                    'return_value' => 'yes',
                    'default' => '',
                ]
            );

            $this->add_control(
                'custom_product_text',
                [
                    'label' => __( 'متن دلخواه محصول', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::TEXTAREA,
                    'rows'  => 6,
                    'default' => '',
                    'description' => __( 'این متن بدون توجه به کوئری یا محصول انتخاب‌شده نمایش داده می‌شود وقتی گزینه بالا فعال باشد.', 'spotplayer-landing' ),
                    'condition' => [ 'use_custom_product_text' => 'yes' ],
                ]
            );

            $this->end_controls_section();

            // Style: Box
            $this->start_controls_section(
                'style_box_section',
                [
                    'label' => __( 'استایل باکس', 'spotplayer-landing' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'box_text_color',
                [
                    'label' => __( 'رنگ متن باکس', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'box_typography',
                    'label' => __( 'فونت باکس', 'spotplayer-landing' ),
                    'selector' => '',
                ]
            );

            $this->end_controls_section();

            // Style: Form Box
            $this->start_controls_section(
                'style_form_section',
                [
                    'label' => __( 'استایل فرم', 'spotplayer-landing' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'form_bg_color',
                [
                    'label' => __( 'رنگ پس‌زمینه فرم', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                ]
            );

            $this->add_control(
                'form_border_radius',
                [
                    'label' => __( 'گردی گوشه‌های فرم (px)', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::NUMBER,
                    'min'   => 0,
                    'max'   => 64,
                    'step'  => 1,
                ]
            );

            $this->add_control(
                'form_padding',
                [
                    'label' => __( 'فاصله داخلی فرم (px)', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::NUMBER,
                    'min'   => 0,
                    'max'   => 64,
                    'step'  => 1,
                ]
            );

            $this->end_controls_section();

            // Style: Inputs
            $this->start_controls_section(
                'style_inputs_section',
                [
                    'label' => __( 'استایل ورودی‌ها', 'spotplayer-landing' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'input_bg_color',
                [
                    'label' => __( 'پس‌زمینه اینپوت', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                ]
            );

            $this->add_control(
                'input_text_color',
                [
                    'label' => __( 'رنگ متن اینپوت', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                ]
            );

            $this->add_control(
                'input_border_color',
                [
                    'label' => __( 'رنگ بوردر اینپوت', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                ]
            );

            $this->end_controls_section();

            // Style: Buttons
            $this->start_controls_section(
                'style_buttons_section',
                [
                    'label' => __( 'استایل دکمه‌ها', 'spotplayer-landing' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'button_bg_color',
                [
                    'label' => __( 'پس‌زمینه دکمه', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                ]
            );

            $this->add_control(
                'button_text_color',
                [
                    'label' => __( 'رنگ متن دکمه', 'spotplayer-landing' ),
                    'type'  => \Elementor\Controls_Manager::COLOR,
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'button_typography',
                    'label' => __( 'فونت دکمه', 'spotplayer-landing' ),
                    'selector' => '',
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();

            // Render shortcode output
            $html = do_shortcode( '[spotplayer_landing]' );

            // Override description if requested
            if ( ! empty( $settings['use_custom_product_text'] ) && $settings['use_custom_product_text'] === 'yes' ) {
                $custom = wp_kses_post( $settings['custom_product_text'] );
                if ( $custom !== '' ) {
                    $html = preg_replace( '/<p class=\"product-desc\">.*?<\\/p>/s', '<p class=\"product-desc\">' . $custom . '<\\/p>', $html );
                }
            } elseif ( ! empty( $settings['product_short_desc'] ) ) {
                $desc = wp_kses_post( $settings['product_short_desc'] );
                $html = preg_replace( '/<p class=\"product-desc\">.*?<\\/p>/s', '<p class=\"product-desc\">' . $desc . '<\\/p>', $html );
            }

            $wid = esc_attr( $this->get_id() );
            $wrap_class = 'spl-el-widget-' . $wid;

            // Build scoped styles
            $css = '';
            if ( ! empty( $settings['box_text_color'] ) ) {
                $css .= ".{$wrap_class} .box-sell{color:{$settings['box_text_color']};}";
            }
            if ( ! empty( $settings['form_bg_color'] ) ) {
                $css .= ".{$wrap_class} .purchase-form{background:{$settings['form_bg_color']};}";
            }
            if ( isset( $settings['form_border_radius'] ) && $settings['form_border_radius'] !== '' ) {
                $css .= ".{$wrap_class} .purchase-form{border-radius:{$settings['form_border_radius']}px;}";
            }
            if ( isset( $settings['form_padding'] ) && $settings['form_padding'] !== '' ) {
                $css .= ".{$wrap_class} .purchase-form{padding:{$settings['form_padding']}px;}";
            }
            if ( ! empty( $settings['input_bg_color'] ) ) {
                $css .= ".{$wrap_class} .purchase-form input{background:{$settings['input_bg_color']};}";
            }
            if ( ! empty( $settings['input_text_color'] ) ) {
                $css .= ".{$wrap_class} .purchase-form input{color:{$settings['input_text_color']};}";
            }
            if ( ! empty( $settings['input_border_color'] ) ) {
                $css .= ".{$wrap_class} .purchase-form input{border-color:{$settings['input_border_color']};}";
            }
            if ( ! empty( $settings['button_bg_color'] ) ) {
                $css .= ".{$wrap_class} .btn{background:{$settings['button_bg_color']};}";
            }
            if ( ! empty( $settings['button_text_color'] ) ) {
                $css .= ".{$wrap_class} .btn{color:{$settings['button_text_color']};}";
            }

            echo '<div class=\"spl-el-widget ' . esc_attr( $wrap_class ) . '\">';
            if ( $css ) {
                echo '<style>' . $css . '</style>';
            }
            echo $html;
            echo '</div>';
        }
    }
}