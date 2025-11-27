<?php
/**
 * Elementor widget for Resort Booking calendar.
 *
 * @package ResortBooking
 */

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor widget to render the booking form via shortcode.
 */
class Resort_Booking_Elementor_Widget extends Widget_Base {
	/**
	 * Widget slug.
	 */
	public function get_name() {
		return 'resort-booking-calendar';
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
				'label' => __( 'Booking Settings', 'resort-booking' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'resort-booking' ),
				'type'        => Controls_Manager::NUMBER,
				'placeholder' => __( 'Enter WooCommerce product ID', 'resort-booking' ),
				'description' => __( 'Choose the bookable product that should power this calendar and button.', 'resort-booking' ),
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

		echo do_shortcode( sprintf( '[resort_booking product_id="%d"]', $product_id ) );
	}
}
