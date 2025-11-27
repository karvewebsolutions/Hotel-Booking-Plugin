<?php
/**
 * Elementor integration.
 *
 * @package ResortBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Elementor widgets for the plugin.
 */
class Resort_Booking_Elementor {
	/**
	 * Hook Elementor registration.
	 */
	public function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the resort booking widget when Elementor is available.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager instance.
	 */
	public function register_widget( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once RESORT_BOOKING_PATH . 'includes/class-resort-elementor-widget.php';
		$widgets_manager->register( new Resort_Booking_Elementor_Widget() );
	}
}
