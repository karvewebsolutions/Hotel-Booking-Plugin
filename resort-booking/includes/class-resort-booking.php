<?php
/**
 * Main plugin loader.
 *
 * @package ResortBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Class Resort_Booking
 */
class Resort_Booking {
/**
 * Singleton.
 *
 * @var Resort_Booking
 */
private static $instance;

/**
 * Plugin version.
 *
 * @var string
 */
public $version = '1.0.0';

/**
 * Get instance.
 *
 * @return Resort_Booking
 */
public static function get_instance() {
if ( null === self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

/**
 * Resort_Booking constructor.
 */
private function __construct() {
$this->define_constants();
$this->includes();
$this->init_hooks();
}

/**
 * Define plugin constants.
 */
private function define_constants() {
define( 'RESORT_BOOKING_VERSION', $this->version );
define( 'RESORT_BOOKING_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
define( 'RESORT_BOOKING_URL', plugin_dir_url( dirname( __FILE__ ) ) );
}

/**
 * Include required files.
 */
private function includes() {
require_once RESORT_BOOKING_PATH . 'includes/class-resort-admin.php';
require_once RESORT_BOOKING_PATH . 'includes/class-resort-frontend.php';
require_once RESORT_BOOKING_PATH . 'includes/class-resort-wc.php';
require_once RESORT_BOOKING_PATH . 'includes/class-resort-elementor.php';
}

/**
 * Hook everything into WordPress.
 */
private function init_hooks() {
new Resort_Booking_Admin();
new Resort_Booking_Frontend();
new Resort_Booking_WC();
new Resort_Booking_Elementor();
}
}
