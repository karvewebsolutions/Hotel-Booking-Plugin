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
add_action( 'template_redirect', array( $this, 'maybe_handle_booking_form' ) );
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
	$accoms     = get_post_meta( $product_id, '_resort_accommodations', true );
	$blocked    = is_array( $blocked ) ? $blocked : array();
	$accoms     = is_array( $accoms ) ? $accoms : array();
	$default_accom = ( ! empty( $accoms ) && isset( $accoms[0]['name'] ) ) ? sanitize_text_field( $accoms[0]['name'] ) : '';

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
	WC()->session->set( 'resort_booking_accommodation', $default_accom );
	WC()->session->set( 'resort_booking_adults', 0 );
	WC()->session->set( 'resort_booking_children', 0 );
	WC()->session->set( 'resort_payment_option', 'full' );
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
	$forced = $product ? get_post_meta( $product->get_id(), '_resort_forced_date', true ) : '';
	$has_forced_date = ! empty( $forced );
        $accom  = $product ? get_post_meta( $product->get_id(), '_resort_accommodations', true ) : array();
        $accom  = is_array( $accom ) ? $accom : array();
        $meta_child = $product ? floatval( get_post_meta( $product->get_id(), '_resort_child_price', true ) ) : 0;
        $default_accom = $has_forced_date && ! empty( $accom ) && isset( $accom[0]['name'] ) ? $accom[0]['name'] : '';
        $session_accom = sanitize_text_field( WC()->session->get( 'resort_booking_accommodation', '' ) );
        $selected_accom = $session_accom ? $session_accom : $default_accom;
        $adults = absint( WC()->session->get( 'resort_booking_adults', 0 ) );
        $children = absint( WC()->session->get( 'resort_booking_children', 0 ) );
        $payment_option = sanitize_text_field( WC()->session->get( 'resort_payment_option', 'full' ) );
        $has_child_pricing = ( $meta_child > 0 );

        foreach ( $accom as $row ) {
                if ( isset( $row['child'] ) && floatval( $row['child'] ) > 0 ) {
                        $has_child_pricing = true;
                        break;
                }
        }

        if ( ! $has_child_pricing ) {
                $children = 0;
                WC()->session->set( 'resort_booking_children', 0 );
        }
        ?>
<div class="resort-booking-checkout">
        <h3><?php esc_html_e( 'Resort Booking Details', 'resort-booking' ); ?></h3>
        <?php if ( ! $has_forced_date ) : ?>
                <p class="form-row form-row-wide">
                <label for="resort_booking_accommodation"><?php esc_html_e( 'Accommodation', 'resort-booking' ); ?></label>
                <select name="resort_booking_accommodation" id="resort_booking_accommodation" class="input-text">
                <option value=""><?php esc_html_e( 'Select option', 'resort-booking' ); ?></option>
                <?php foreach ( $accom as $row ) : ?>
                <option value="<?php echo esc_attr( $row['name'] ); ?>" <?php selected( $selected_accom, $row['name'] ); ?> data-adult="<?php echo esc_attr( $row['adult'] ); ?>" data-child="<?php echo esc_attr( $row['child'] ); ?>"><?php echo esc_html( $row['name'] ); ?></option>
                <?php endforeach; ?>
                </select>
                </p>
        <?php else : ?>
            <?php if ( $default_accom ) : ?>
                <input type="hidden" name="resort_booking_accommodation" value="<?php echo esc_attr( $selected_accom ); ?>" />
            <?php endif; ?>
        <?php endif; ?>
	<div class="resort-booking-row">
        <p class="form-row">
        <label for="resort_booking_adults"><?php esc_html_e( 'Adults', 'resort-booking' ); ?></label>
        <input type="number" min="0" name="resort_booking_adults" id="resort_booking_adults" value="<?php echo esc_attr( $adults ); ?>" class="input-text" />
        </p>
        <?php if ( $has_child_pricing ) : ?>
                <p class="form-row">
                <label for="resort_booking_children"><?php esc_html_e( 'Children', 'resort-booking' ); ?></label>
                <input type="number" min="0" name="resort_booking_children" id="resort_booking_children" value="<?php echo esc_attr( $children ); ?>" class="input-text" />
                </p>
        <?php endif; ?>
	</div>
	<div class="resort-booking-row">
	<p class="form-row form-row-first">
	<label><?php esc_html_e( 'Booking date', 'resort-booking' ); ?></label>
<input type="text" name="resort_booking_date" value="<?php echo esc_attr( $date ); ?>" readonly />
</p>
        <p class="form-row form-row-last resort-payment-options">
        <span class="resort-payment-label"><?php esc_html_e( 'Payment option', 'resort-booking' ); ?></span>
        <span class="resort-payment-group">
                <label class="resort-payment-choice"><input type="radio" name="resort_payment_option" value="full" <?php checked( $payment_option, 'full' ); ?> /> <?php esc_html_e( 'Full', 'resort-booking' ); ?></label>
                <label class="resort-payment-choice"><input type="radio" name="resort_payment_option" value="deposit" <?php checked( $payment_option, 'deposit' ); ?> /> <?php esc_html_e( '50%', 'resort-booking' ); ?></label>
        </span>
        </p>
	</div>

        </div>
        <div class="resort-summary-target"></div>
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
        $children = isset( $_POST['resort_booking_children'] ) ? absint( $_POST['resort_booking_children'] ) : 0;
        $product = $this->get_cart_product();
        $blocked = $product ? get_post_meta( $product->get_id(), '_resort_blocked_dates', true ) : array();
        $blocked = is_array( $blocked ) ? $blocked : array();
        $forced  = $product ? get_post_meta( $product->get_id(), '_resort_forced_date', true ) : '';
        $disable = $product ? get_post_meta( $product->get_id(), '_resort_disable_date_selection', true ) : '';
        $accoms  = $product ? get_post_meta( $product->get_id(), '_resort_accommodations', true ) : array();
        $accoms  = is_array( $accoms ) ? $accoms : array();
        $has_forced_date = ! empty( $forced );
        $default_accom = $has_forced_date && ! empty( $accoms ) && isset( $accoms[0]['name'] ) ? sanitize_text_field( $accoms[0]['name'] ) : '';
        $has_child_pricing = $product ? floatval( get_post_meta( $product->get_id(), '_resort_child_price', true ) ) > 0 : false;

        foreach ( $accoms as $row ) {
                if ( isset( $row['child'] ) && floatval( $row['child'] ) > 0 ) {
                        $has_child_pricing = true;
                        break;
                }
        }

        if ( ! $has_child_pricing ) {
                $children = 0;
        }

if ( $disable && $forced ) {
$date = $forced;
}

if ( empty( $date ) ) {
wc_add_notice( esc_html__( 'Please provide a booking date.', 'resort-booking' ), 'error' );
}

if ( in_array( $date, $blocked, true ) ) {
wc_add_notice( esc_html__( 'Selected date is blocked.', 'resort-booking' ), 'error' );
}

        if ( $has_forced_date && empty( $accom ) && $default_accom ) {
$accom = $default_accom;
}

if ( ! $has_forced_date && empty( $accom ) ) {
wc_add_notice( esc_html__( 'Select accommodation.', 'resort-booking' ), 'error' );
}

        if ( ( $adults + $children ) < 1 ) {
        wc_add_notice( esc_html__( 'Add at least one guest.', 'resort-booking' ), 'error' );
        }

WC()->session->set( 'resort_booking_date', $date );
WC()->session->set( 'resort_booking_accommodation', $accom );
WC()->session->set( 'resort_booking_adults', $adults );
WC()->session->set( 'resort_booking_children', $children );
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
$product = $this->get_cart_product();
if ( ! $product ) {
return;
}

        if ( ! $this->is_booking_product( $product ) ) {
                return;
        }

        $amounts = $this->get_booking_amounts( $product, true );

        if ( empty( $amounts ) ) {
                return;
        }

        if ( $amounts['total'] > 0 ) {
                WC()->cart->add_fee( __( 'Booking Charge', 'resort-booking' ), $amounts['fee'] );
        } else {
                WC()->cart->add_fee( __( 'Booking Charge', 'resort-booking' ), 0 );
        }
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

                        if ( ! $this->is_booking_product( $product ) ) {
                                continue;
                        }

                        $product->set_price( 0 );
                }
        }

    /**
     * Determine if a product uses booking pricing metadata.
     *
     * @param WC_Product $product Product instance.
     *
     * @return bool
     */
    private function is_booking_product( $product ) {
        $accoms     = get_post_meta( $product->get_id(), '_resort_accommodations', true );
        $accoms     = is_array( $accoms ) ? $accoms : array();
        $meta_adult = floatval( get_post_meta( $product->get_id(), '_resort_adult_price', true ) );
        $meta_child = floatval( get_post_meta( $product->get_id(), '_resort_child_price', true ) );

        if ( empty( $accoms ) && $meta_adult <= 0 && $meta_child <= 0 ) {
            return false;
        }

        return true;
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
echo '<p class="resort-remaining resort-summary"><span class="resort-summary-label">' . esc_html__( 'Remaining balance:', 'resort-booking' ) . '</span> ' . wp_kses_post( wc_price( $remaining ) ) . '</p>';
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
        if ( WC()->cart ) {
            WC()->cart->calculate_totals();
        }

        $product   = $this->get_cart_product();
        $amounts   = $product ? $this->get_booking_amounts( $product, true ) : array();
        $total     = isset( $amounts['total'] ) ? $amounts['total'] : WC()->session->get( 'resort_total_amount', 0 );
        $remaining = isset( $amounts['remaining'] ) ? $amounts['remaining'] : WC()->session->get( 'resort_remaining_balance', 0 );
        
        $output = '';
        if ( $remaining > 0 ) {
            $output .= '<div class="resort-summary">' . sprintf( __( 'Remaining balance: %s', 'resort-booking' ), wc_price( $remaining ) ) . '</div>';
            $output .= '<div class="resort-summary">' . sprintf( __( 'Total amount: %s', 'resort-booking' ), wc_price( $total ) ) . '</div>';
        } else {
            $output .= '<div class="resort-summary">' . sprintf( __( 'Total amount: %s', 'resort-booking' ), wc_price( $total ) ) . '</div>';
        }
        
        echo wp_kses_post( $output );
        die();
    }

    /**
     * Calculate booking totals without mutating the cart.
     *
     * @param WC_Product $product Product object.
     * @param bool       $persist_session Whether to persist derived values to the session.
     * @return array|null
     */
    private function get_booking_amounts( $product, $persist_session = false ) {
        if ( ! $product || ! $this->is_booking_product( $product ) ) {
                return null;
        }

        $forced         = get_post_meta( $product->get_id(), '_resort_forced_date', true );
        $has_forced_date = ! empty( $forced );
        $accoms         = get_post_meta( $product->get_id(), '_resort_accommodations', true );
        $accoms         = is_array( $accoms ) ? $accoms : array();
        $meta_adult     = floatval( get_post_meta( $product->get_id(), '_resort_adult_price', true ) );
        $meta_child     = floatval( get_post_meta( $product->get_id(), '_resort_child_price', true ) );

        if ( empty( $accoms ) && $meta_adult <= 0 && $meta_child <= 0 ) {
                return null;
        }

        // Booking line prices are zeroed in maybe_zero_cart_price() to avoid quantity scaling.
        $adults        = absint( WC()->session->get( 'resort_booking_adults', 1 ) );
        $children      = absint( WC()->session->get( 'resort_booking_children', 0 ) );
        $selected      = sanitize_text_field( WC()->session->get( 'resort_booking_accommodation', '' ) );
        $default_accom = $has_forced_date && ! empty( $accoms ) && isset( $accoms[0]['name'] ) ? $accoms[0]['name'] : '';
        $selected      = ( empty( $selected ) && $default_accom ) ? $default_accom : $selected;
        $selected      = sanitize_text_field( $selected );

        if ( $persist_session && empty( WC()->session->get( 'resort_booking_accommodation', '' ) ) && $selected ) {
                WC()->session->set( 'resort_booking_accommodation', $selected );
        }

        $payment = sanitize_text_field( WC()->session->get( 'resort_payment_option', 'full' ) );

        $adult_rate = 0;
        $child_rate = 0;
        foreach ( $accoms as $row ) {
                if ( isset( $row['name'] ) && $selected === $row['name'] ) {
                        $adult_rate = floatval( $row['adult'] );
                        $child_rate = floatval( $row['child'] );
                        break;
                }
        }

        // Fallback to first accommodation if none matched.
        if ( ( 0 === $adult_rate && 0 === $child_rate ) && ! empty( $accoms ) && isset( $accoms[0]['adult'], $accoms[0]['child'] ) ) {
                $adult_rate = floatval( $accoms[0]['adult'] );
                $child_rate = floatval( $accoms[0]['child'] );
        }

        // If we still have no rates, use meta overrides.
        if ( 0 === $adult_rate && $meta_adult > 0 ) {
                $adult_rate = $meta_adult;
        }
        if ( 0 === $child_rate && $meta_child > 0 ) {
                $child_rate = $meta_child;
        }

        // If child rate is still zero, mirror adult rate so totals grow with headcount.
        if ( 0 === $child_rate ) {
                $child_rate = $adult_rate;
        }

        $total     = ( $adults * $adult_rate ) + ( $children * $child_rate );
        $fee       = ( 'deposit' === $payment ) ? $total * 0.5 : $total;
        $remaining = ( 'deposit' === $payment ) ? $total - $fee : 0;

        if ( $persist_session ) {
                WC()->session->set( 'resort_remaining_balance', $remaining );
        }

        return array(
                'total'     => $total,
                'fee'       => $fee,
                'remaining' => $remaining,
        );
    }

/**
 * Helper to fetch first cart product.
 *
 * @return WC_Product|null
 */
    private function get_cart_product() {
        $cart = WC()->cart;

        if ( ! $cart || $cart->is_empty() ) {
            return null;
        }

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
                continue;
            }

            $product    = $cart_item['data'];
            $accoms     = get_post_meta( $product->get_id(), '_resort_accommodations', true );
            $accoms     = is_array( $accoms ) ? $accoms : array();
            $meta_adult = floatval( get_post_meta( $product->get_id(), '_resort_adult_price', true ) );
            $meta_child = floatval( get_post_meta( $product->get_id(), '_resort_child_price', true ) );

            if ( ! empty( $accoms ) || $meta_adult > 0 || $meta_child > 0 ) {
                return $product;
            }
        }

        return null;
    }
}
