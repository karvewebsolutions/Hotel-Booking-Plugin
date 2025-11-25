<?php
/**
 * WooCommerce integration for Resort Booking.
 *
 * @package ResortBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Handles checkout fields, fees, and AJAX.
 */
class Resort_Booking_WC {
/**
 * Constructor hooks WC actions.
 */
public function __construct() {
add_action( 'init', array( $this, 'maybe_handle_booking_form' ) );
add_action( 'woocommerce_after_order_notes', array( $this, 'render_checkout_fields' ) );
        add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'render_checkout_sections' ), 5 );
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_fields' ) );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_order_meta' ), 10, 2 );
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_booking_fee' ) );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_zero_cart_price' ) );
        add_filter( 'woocommerce_get_price_html', array( $this, 'maybe_zero_price' ), 10, 2 );
        add_action( 'woocommerce_thankyou', array( $this, 'display_remaining_balance' ) );
        add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'email_meta_fields' ), 10, 3 );
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_gateways_for_deposit' ) );
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'adjust_order_status' ), 10, 3 );
add_action( 'wp_ajax_resort_save_booking_session', array( $this, 'ajax_save_booking_session' ) );
add_action( 'wp_ajax_nopriv_resort_save_booking_session', array( $this, 'ajax_save_booking_session' ) );
add_action( 'wp_ajax_reload_booking_summary', array( $this, 'ajax_reload_booking_summary' ) );
add_action( 'wp_ajax_nopriv_reload_booking_summary', array( $this, 'ajax_reload_booking_summary' ) );
}

/**
 * Handle form submission from shortcode and add product to cart.
 */
public function maybe_handle_booking_form() {
if ( ! isset( $_POST['resort_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['resort_booking_nonce'] ) ), 'resort_booking_form' ) ) {
return;
}

$product_id = isset( $_POST['resort_booking_product_id'] ) ? absint( $_POST['resort_booking_product_id'] ) : 0;
$date       = isset( $_POST['resort_booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_booking_date'] ) ) : '';
$forced     = get_post_meta( $product_id, '_resort_forced_date', true );
$disable    = get_post_meta( $product_id, '_resort_disable_date_selection', true );
$blocked    = get_post_meta( $product_id, '_resort_blocked_dates', true );
$blocked    = is_array( $blocked ) ? $blocked : array();

if ( $disable && $forced ) {
$date = $forced;
} elseif ( in_array( $date, $blocked, true ) ) {
wc_add_notice( esc_html__( 'Selected date is unavailable.', 'resort-booking' ), 'error' );
return;
}

if ( ! $product_id || empty( $date ) ) {
wc_add_notice( esc_html__( 'Booking requires a date.', 'resort-booking' ), 'error' );
return;
}

WC()->cart->empty_cart();
WC()->session->set( 'resort_booking_date', $date );
WC()->cart->add_to_cart( $product_id );
wp_safe_redirect( wc_get_checkout_url() );
exit;
}

/**
 * Render custom checkout fields.
 *
 * @param WC_Checkout $checkout Checkout object.
 */
public function render_checkout_fields( $checkout ) {
$date   = WC()->session->get( 'resort_booking_date' );
$product = $this->get_cart_product();
$accom  = $product ? get_post_meta( $product->get_id(), '_resort_accommodations', true ) : array();
$accom  = is_array( $accom ) ? $accom : array();
?>
<div class="resort-booking-checkout">
<h3><?php esc_html_e( 'Resort Booking Details', 'resort-booking' ); ?></h3>
<p class="form-row form-row-wide">
<label><?php esc_html_e( 'Booking date', 'resort-booking' ); ?></label>
<input type="text" name="resort_booking_date" value="<?php echo esc_attr( $date ); ?>" readonly />
</p>
<p class="form-row form-row-first">
<label for="resort_booking_accommodation"><?php esc_html_e( 'Accommodation', 'resort-booking' ); ?></label>
<select name="resort_booking_accommodation" id="resort_booking_accommodation">
<option value=""><?php esc_html_e( 'Select option', 'resort-booking' ); ?></option>
<?php foreach ( $accom as $row ) : ?>
<option value="<?php echo esc_attr( $row['name'] ); ?>" data-adult="<?php echo esc_attr( $row['adult'] ); ?>" data-child="<?php echo esc_attr( $row['child'] ); ?>"><?php echo esc_html( $row['name'] ); ?></option>
<?php endforeach; ?>
</select>
</p>
<p class="form-row form-row-last">
<label for="resort_booking_adults"><?php esc_html_e( 'Adults', 'resort-booking' ); ?></label>
<input type="number" min="1" name="resort_booking_adults" id="resort_booking_adults" value="1" />
</p>
<p class="form-row form-row-first">
<label for="resort_booking_children"><?php esc_html_e( 'Children', 'resort-booking' ); ?></label>
<input type="number" min="0" name="resort_booking_children" id="resort_booking_children" value="0" />
</p>
<p class="form-row form-row-last">
<label><?php esc_html_e( 'Payment option', 'resort-booking' ); ?></label><br />
<label><input type="radio" name="resort_payment_option" value="full" checked /> <?php esc_html_e( 'Pay Full Amount', 'resort-booking' ); ?></label><br />
<label><input type="radio" name="resort_payment_option" value="deposit" /> <?php esc_html_e( 'Pay 50% Deposit', 'resort-booking' ); ?></label>
</p>
</div>
<?php
}

/**
 * Render checkout sections in stored order for flexibility.
 */
public function render_checkout_sections() {
$order = get_option( 'wc_checkout_section_order', array( 'billing', 'shipping', 'order', 'payment' ) );
if ( ! is_array( $order ) ) {
return;
}

echo '<div class="resort-checkout-order">';
foreach ( $order as $section ) {
do_action( 'wc_checkout_section_' . sanitize_key( $section ) );
}
echo '</div>';
}

/**
 * Validate checkout fields.
 */
public function validate_checkout_fields() {
$date   = isset( $_POST['resort_booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_booking_date'] ) ) : '';
$accom  = isset( $_POST['resort_booking_accommodation'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_booking_accommodation'] ) ) : '';
$adults = isset( $_POST['resort_booking_adults'] ) ? absint( $_POST['resort_booking_adults'] ) : 0;
$product = $this->get_cart_product();
$blocked = $product ? get_post_meta( $product->get_id(), '_resort_blocked_dates', true ) : array();
$blocked = is_array( $blocked ) ? $blocked : array();
$forced  = $product ? get_post_meta( $product->get_id(), '_resort_forced_date', true ) : '';
$disable = $product ? get_post_meta( $product->get_id(), '_resort_disable_date_selection', true ) : '';

if ( $disable && $forced ) {
$date = $forced;
}

if ( empty( $date ) ) {
wc_add_notice( esc_html__( 'Please provide a booking date.', 'resort-booking' ), 'error' );
}

if ( in_array( $date, $blocked, true ) ) {
wc_add_notice( esc_html__( 'Selected date is blocked.', 'resort-booking' ), 'error' );
}

if ( empty( $accom ) ) {
wc_add_notice( esc_html__( 'Select accommodation.', 'resort-booking' ), 'error' );
}

if ( $adults < 1 ) {
wc_add_notice( esc_html__( 'At least one adult required.', 'resort-booking' ), 'error' );
}

WC()->session->set( 'resort_booking_date', $date );
WC()->session->set( 'resort_booking_accommodation', $accom );
WC()->session->set( 'resort_booking_adults', $adults );
WC()->session->set( 'resort_booking_children', isset( $_POST['resort_booking_children'] ) ? absint( $_POST['resort_booking_children'] ) : 0 );
WC()->session->set( 'resort_payment_option', isset( $_POST['resort_payment_option'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_payment_option'] ) ) : 'full' );
}

/**
 * Save order meta.
 *
 * @param WC_Order $order Order object.
 * @param array    $data  Posted data.
 */
public function save_order_meta( $order, $data ) {
$order->update_meta_data( '_resort_booking_date', WC()->session->get( 'resort_booking_date' ) );
$order->update_meta_data( '_resort_booking_accommodation', WC()->session->get( 'resort_booking_accommodation' ) );
$order->update_meta_data( '_resort_booking_adults', WC()->session->get( 'resort_booking_adults' ) );
$order->update_meta_data( '_resort_booking_children', WC()->session->get( 'resort_booking_children' ) );
$order->update_meta_data( '_resort_payment_option', WC()->session->get( 'resort_payment_option' ) );
$order->update_meta_data( '_resort_remaining_balance', WC()->session->get( 'resort_remaining_balance' ) );
}

/**
 * Calculate booking fee based on accommodations.
 */
public function calculate_booking_fee() {
    $amounts = $this->get_booking_amounts();
}

/**
 * Hide price display for booking product to avoid double-charging.
 *
 * @param string     $price Price HTML.
 * @param WC_Product $product Product.
 * @return string
 */
    public function maybe_zero_price( $price, $product ) {
        $cart_product = $this->get_cart_product();
        if ( $cart_product && $cart_product->get_id() === $product->get_id() ) {
            return wc_price( 0 );
        }
        return $price;
    }

    /**
     * Set cart item price to zero for booking products so totals rely on booking fee.
     *
     * @param WC_Cart $cart Cart object.
     */
    public function maybe_zero_cart_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        if ( ! $cart || $cart->is_empty() ) {
            return;
        }

foreach ( $cart->get_cart() as $cart_item ) {
if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
continue;
}

$product = $cart_item['data'];
$accoms  = get_post_meta( $product->get_id(), '_resort_accommodations', true );
$amounts = $this->get_booking_amounts();

if ( empty( $accoms ) ) {
continue;
}

$product->set_price( floatval( $amounts['charge'] ) );
}
}

/**
 * Calculate booking charge, total, and remaining balance.
 *
 * @return array
 */
private function get_booking_amounts() {
$product = $this->get_cart_product();

if ( ! $product ) {
WC()->session->set( 'resort_remaining_balance', 0 );
WC()->session->set( 'resort_booking_charge', 0 );

return array(
'total'     => 0,
'charge'    => 0,
'remaining' => 0,
);
}

$accoms = get_post_meta( $product->get_id(), '_resort_accommodations', true );
$accoms = is_array( $accoms ) ? $accoms : array();
$adults = absint( WC()->session->get( 'resort_booking_adults', 1 ) );
$children = absint( WC()->session->get( 'resort_booking_children', 0 ) );
$selected = sanitize_text_field( WC()->session->get( 'resort_booking_accommodation', '' ) );
$payment  = sanitize_text_field( WC()->session->get( 'resort_payment_option', 'full' ) );

$adult_rate = 0;
$child_rate = 0;
foreach ( $accoms as $row ) {
if ( isset( $row['name'] ) && $selected === $row['name'] ) {
$adult_rate = floatval( $row['adult'] );
$child_rate = floatval( $row['child'] );
}
}

$total     = ( $adults * $adult_rate ) + ( $children * $child_rate );
$charge    = ( 'deposit' === $payment ) ? $total * 0.5 : $total;
$remaining = ( 'deposit' === $payment ) ? $total - $charge : 0;


WC()->session->set( 'resort_remaining_balance', $remaining );
WC()->session->set( 'resort_booking_charge', $charge );

return array(
'total'     => $total,
'charge'    => $charge,
'remaining' => $remaining,
);
}

/**
 * Display remaining balance on thank you page.
 *
 * @param int $order_id Order ID.
 */
public function display_remaining_balance( $order_id ) {
$order     = wc_get_order( $order_id );
$remaining = $order ? $order->get_meta( '_resort_remaining_balance' ) : 0;
if ( $remaining > 0 ) {
echo '<p class="resort-remaining">' . esc_html( sprintf( __( 'Remaining balance: %s', 'resort-booking' ), wc_price( $remaining ) ) ) . '</p>';
}
}

/**
 * Add booking info to emails.
 */
public function email_meta_fields( $fields, $sent_to_admin, $order ) {
$remaining = $order->get_meta( '_resort_remaining_balance' );
if ( $remaining > 0 ) {
$fields['resort_remaining_balance'] = array(
'label' => __( 'Remaining Balance', 'resort-booking' ),
'value' => wc_price( $remaining ),
);
}
return $fields;
}

/**
 * Hide COD when deposit selected.
 */
public function filter_gateways_for_deposit( $gateways ) {
$payment = WC()->session->get( 'resort_payment_option', 'full' );
if ( 'deposit' === $payment && isset( $gateways['cod'] ) ) {
unset( $gateways['cod'] );
}
return $gateways;
}

/**
 * Set order status to on-hold when deposit paid.
 */
public function adjust_order_status( $order_id, $posted_data, $order ) {
$payment = $order->get_meta( '_resort_payment_option' );
if ( 'deposit' === $payment ) {
$order->update_status( 'on-hold', __( 'Deposit payment - balance remaining.', 'resort-booking' ), true );
}
}

/**
 * AJAX: save booking fields in session.
 */
public function ajax_save_booking_session() {
check_ajax_referer( 'resort_booking', 'nonce' );
WC()->session->set( 'resort_booking_date', isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '' );
WC()->session->set( 'resort_booking_accommodation', isset( $_POST['accommodation'] ) ? sanitize_text_field( wp_unslash( $_POST['accommodation'] ) ) : '' );
WC()->session->set( 'resort_booking_adults', isset( $_POST['adults'] ) ? absint( $_POST['adults'] ) : 0 );
WC()->session->set( 'resort_booking_children', isset( $_POST['children'] ) ? absint( $_POST['children'] ) : 0 );
WC()->session->set( 'resort_payment_option', isset( $_POST['payment'] ) ? sanitize_text_field( wp_unslash( $_POST['payment'] ) ) : 'full' );
wp_send_json_success();
}

/**
 * AJAX: reload summary.
 */
public function ajax_reload_booking_summary() {
$remaining = WC()->session->get( 'resort_remaining_balance', 0 );
echo wp_kses_post( '<div class="resort-summary">' . sprintf( __( 'Remaining balance: %s', 'resort-booking' ), wc_price( $remaining ) ) . '</div>' );
die();
}

/**
 * Helper to fetch first cart product.
 *
 * @return WC_Product|null
 */
private function get_cart_product() {
$cart = WC()->cart;
if ( ! $cart || empty( $cart->get_cart() ) ) {
return null;
}
$items = $cart->get_cart();
$first = reset( $items );
return isset( $first['data'] ) ? $first['data'] : null;
}
}
