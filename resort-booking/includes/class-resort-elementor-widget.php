<?php
/**
 * Elementor widgets for Resort Booking calendar and button layouts.
 *
 * @package ResortBooking
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared helpers for Resort Booking Elementor widgets.
 */
abstract class Resort_Booking_Elementor_Base_Widget extends Widget_Base {
    /**
     * Add the product ID control used across widgets.
     */
    protected function add_product_control() {
        $this->add_control(
            'product_id',
            array(
                'label'       => __( 'Product ID', 'resort-booking' ),
                'type'        => Controls_Manager::NUMBER,
                'placeholder' => __( 'Enter WooCommerce product ID', 'resort-booking' ),
                'description' => __( 'Choose the bookable product this widget should power.', 'resort-booking' ),
            )
        );
    }

    /**
     * Register a responsive alignment control.
     *
     * @param string $selector CSS selector.
     * @param string $control_id Control ID.
     * @param string $label Optional label.
     */
    protected function add_alignment_control( $selector, $control_id = 'alignment', $label = null ) {
        $this->add_responsive_control(
            $control_id,
            array(
                'label'     => $label ? $label : __( 'Alignment', 'resort-booking' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => array(
                    'left'   => array(
                        'title' => __( 'Left', 'resort-booking' ),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __( 'Center', 'resort-booking' ),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right'  => array(
                        'title' => __( 'Right', 'resort-booking' ),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'default'   => 'center',
                'toggle'    => true,
                'selectors' => array(
                    '{{WRAPPER}} ' . $selector => 'text-align: {{VALUE}};',
                ),
            )
        );
    }

    /**
     * Add common button style controls.
     *
     * @param string $selector CSS selector.
     */
    protected function add_button_style_controls( $selector ) {
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} ' . $selector,
            )
        );

        $this->add_control(
            'button_text_color',
            array(
                'label'     => __( 'Text Color', 'resort-booking' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_background_color',
            array(
                'label'     => __( 'Background Color', 'resort-booking' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $selector => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'button_padding',
            array(
                'label'      => __( 'Padding', 'resort-booking' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', 'rem' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'button_border_radius',
            array(
                'label'      => __( 'Border Radius', 'resort-booking' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
    }
}

/**
 * Elementor widget: Book button only.
 */
class Resort_Booking_Elementor_Button_Widget extends Resort_Booking_Elementor_Base_Widget {
    /**
     * Widget slug.
     */
    public function get_name() {
        return 'resort-booking-button-only';
    }

    /**
     * Widget title in Elementor panel.
     */
    public function get_title() {
        return __( 'Resort Book Now Button', 'resort-booking' );
    }

    /**
     * Icon identifier.
     */
    public function get_icon() {
        return 'eicon-button';
    }

    /**
     * Widget categories.
     */
    public function get_categories() {
        return array( 'general' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Button Settings', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_product_control();

        $this->add_control(
            'button_text',
            array(
                'label'       => __( 'Button Text', 'resort-booking' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Book Now', 'resort-booking' ),
                'placeholder' => __( 'Enter button label', 'resort-booking' ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'button_style_section',
            array(
                'label' => __( 'Button Style', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_alignment_control( '.resort-elementor-button-only' );
        $this->add_button_style_controls( '.resort-elementor-button-only .resort-booking-submit' );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend and in the editor.
     */
    protected function render() {
        $settings   = $this->get_settings_for_display();
        $product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        if ( ! $product_id ) {
            echo esc_html__( 'Please set a Product ID to display the booking button.', 'resort-booking' );
            return;
        }

        $button_label = ! empty( $settings['button_text'] ) ? sanitize_text_field( $settings['button_text'] ) : __( 'Book Now', 'resort-booking' );
        $shortcode    = sprintf( '[resort_booking product_id="%d" show_calendar="no" show_button="yes" button_label="%s"]', $product_id, esc_attr( $button_label ) );

        echo '<div class="resort-elementor-widget resort-elementor-button-only">' . do_shortcode( $shortcode ) . '</div>';
    }
}

/**
 * Elementor widget: Calendar only.
 */
class Resort_Booking_Elementor_Calendar_Widget extends Resort_Booking_Elementor_Base_Widget {
    /**
     * Widget slug.
     */
    public function get_name() {
        return 'resort-booking-calendar-only';
    }

    /**
     * Widget title in Elementor panel.
     */
    public function get_title() {
        return __( 'Resort Booking Calendar', 'resort-booking' );
    }

    /**
     * Icon identifier.
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Widget categories.
     */
    public function get_categories() {
        return array( 'general' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Calendar Settings', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_product_control();

        $this->end_controls_section();

        $this->start_controls_section(
            'calendar_style_section',
            array(
                'label' => __( 'Calendar Style', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_alignment_control( '.resort-elementor-calendar-only' );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'calendar_label_typography',
                'selector' => '{{WRAPPER}} .resort-elementor-calendar-only label, {{WRAPPER}} .resort-elementor-calendar-only .flatpickr-calendar',
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend and in the editor.
     */
    protected function render() {
        $settings   = $this->get_settings_for_display();
        $product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        if ( ! $product_id ) {
            echo esc_html__( 'Please set a Product ID to display the booking calendar.', 'resort-booking' );
            return;
        }

        $shortcode = sprintf( '[resort_booking product_id="%d" show_calendar="yes" show_button="no"]', $product_id );

        echo '<div class="resort-elementor-widget resort-elementor-calendar-only">' . do_shortcode( $shortcode ) . '</div>';
    }
}

/**
 * Elementor widget: Calendar with Book button.
 */
class Resort_Booking_Elementor_Calendar_Button_Widget extends Resort_Booking_Elementor_Base_Widget {
    /**
     * Widget slug.
     */
    public function get_name() {
        return 'resort-booking-calendar-button';
    }

    /**
     * Widget title in Elementor panel.
     */
    public function get_title() {
        return __( 'Resort Calendar & Book Button', 'resort-booking' );
    }

    /**
     * Icon identifier.
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Widget categories.
     */
    public function get_categories() {
        return array( 'general' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Calendar & Button Settings', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_product_control();

        $this->add_control(
            'button_text',
            array(
                'label'       => __( 'Button Text', 'resort-booking' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Book Now', 'resort-booking' ),
                'placeholder' => __( 'Enter button label', 'resort-booking' ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'calendar_style_section',
            array(
                'label' => __( 'Calendar Style', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_alignment_control( '.resort-elementor-calendar-button .resort-booking-calendar-wrapper', 'calendar_alignment', __( 'Calendar Alignment', 'resort-booking' ) );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'calendar_typography',
                'selector' => '{{WRAPPER}} .resort-elementor-calendar-button label, {{WRAPPER}} .resort-elementor-calendar-button .flatpickr-calendar',
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'button_style_section',
            array(
                'label' => __( 'Button Style', 'resort-booking' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_alignment_control( '.resort-elementor-calendar-button .resort-booking-button-wrapper', 'button_alignment', __( 'Button Alignment', 'resort-booking' ) );
        $this->add_button_style_controls( '.resort-elementor-calendar-button .resort-booking-submit' );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend and in the editor.
     */
    protected function render() {
        $settings   = $this->get_settings_for_display();
        $product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        if ( ! $product_id ) {
            echo esc_html__( 'Please set a Product ID to display the booking calendar.', 'resort-booking' );
            return;
        }

        $button_label = ! empty( $settings['button_text'] ) ? sanitize_text_field( $settings['button_text'] ) : __( 'Book Now', 'resort-booking' );
        $shortcode    = sprintf( '[resort_booking product_id="%d" show_calendar="yes" show_button="yes" button_label="%s"]', $product_id, esc_attr( $button_label ) );

        echo '<div class="resort-elementor-widget resort-elementor-calendar-button">' . do_shortcode( $shortcode ) . '</div>';
    }
}
