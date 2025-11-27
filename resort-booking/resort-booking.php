<?php
/**
 * Plugin Name: Resort Booking
 * Plugin URI: https://karveweb.solutions/resort-booking
 * Description: Resort Booking integrates WooCommerce with date-based booking, deposits, and checkout customization.
 * Version: 1.1.5
 * Author: Karveweb.solutions
 * Text Domain: resort-booking
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

// Autoload plugin classes.
spl_autoload_register(
function ( $class ) {
if ( 0 !== strpos( $class, 'Resort_Booking' ) ) {
return;
}

// Base loader for the main class and supporting classes following class-resort-*.php naming.
$base_name = 'class-resort-booking.php';
if ( 'Resort_Booking' === $class ) {
$path = plugin_dir_path( __FILE__ ) . 'includes/' . $base_name;
} else {
$filename = strtolower( str_replace( 'Resort_Booking_', 'class-resort-', $class ) );
$path     = plugin_dir_path( __FILE__ ) . 'includes/' . $filename . '.php';
}

if ( isset( $path ) && file_exists( $path ) ) {
require_once $path;
}
}
);

/**
 * Bootstrap the plugin once plugins are loaded.
 */
function resort_booking_init() {
if ( ! class_exists( 'WooCommerce' ) ) {
return;
}

Resort_Booking::get_instance();
}
add_action( 'plugins_loaded', 'resort_booking_init' );
