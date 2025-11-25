<?php
/**
 * Admin functionality for Resort Booking.
 *
 * @package ResortBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Handles admin UI such as metaboxes and bulk date assignment.
 */
class Resort_Booking_Admin {
/**
 * Constructor hooks admin actions.
 */
public function __construct() {
add_action( 'add_meta_boxes_product', array( $this, 'add_product_metabox' ) );
add_action( 'save_post_product', array( $this, 'save_product_meta' ), 10, 2 );
add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
add_action( 'wp_ajax_resort_save_checkout_section_order', array( $this, 'save_checkout_section_order' ) );
}

/**
 * Register admin pages.
 */
public function register_admin_menus() {
add_submenu_page(
'woocommerce',
esc_html__( 'Resort Booking Dates', 'resort-booking' ),
esc_html__( 'Resort Booking Dates', 'resort-booking' ),
'manage_woocommerce',
'resort-booking-dates',
array( $this, 'render_bulk_dates_page' )
);

add_submenu_page(
'woocommerce',
esc_html__( 'Checkout Section Order', 'resort-booking' ),
esc_html__( 'Checkout Section Order', 'resort-booking' ),
'manage_woocommerce',
'resort-checkout-order',
array( $this, 'render_checkout_order_page' )
);
}

/**
 * Add metabox to product editor.
 */
public function add_product_metabox() {
add_meta_box(
'resort-booking-metabox',
esc_html__( 'Resort Booking Dates', 'resort-booking' ),
array( $this, 'render_product_metabox' ),
'product',
'side',
'default'
);
}

/**
 * Render product metabox fields.
 *
 * @param WP_Post $post Post object.
 */
public function render_product_metabox( $post ) {
	$forced_date = get_post_meta( $post->ID, '_resort_forced_date', true );
	$disable     = get_post_meta( $post->ID, '_resort_disable_date_selection', true );
	$blocked     = get_post_meta( $post->ID, '_resort_blocked_dates', true );
	$blocked     = is_array( $blocked ) ? $blocked : array();
	$adult_price = get_post_meta( $post->ID, '_resort_adult_price', true );
	$child_price = get_post_meta( $post->ID, '_resort_child_price', true );

	wp_nonce_field( 'resort_booking_meta', 'resort_booking_meta_nonce' );
	?>
	<p>
	<label for="resort_forced_date"><strong><?php esc_html_e( 'Desired booking date', 'resort-booking' ); ?></strong></label><br />
<input type="date" name="resort_forced_date" id="resort_forced_date" value="<?php echo esc_attr( $forced_date ); ?>" />
</p>
<p>
<label>
<input type="checkbox" name="resort_disable_date_selection" value="1" <?php checked( $disable, '1' ); ?> />
<?php esc_html_e( 'Disable date picker & force date', 'resort-booking' ); ?>
</label>
</p>
<div id="resort-blocked-dates-wrapper">
<label><strong><?php esc_html_e( 'Reserved / Blocked dates', 'resort-booking' ); ?></strong></label>
<div class="resort-blocked-repeat">
<?php foreach ( $blocked as $date ) : ?>
<div class="resort-blocked-row">
<input type="date" name="resort_blocked_dates[]" value="<?php echo esc_attr( $date ); ?>" />
<button type="button" class="button resort-remove-blocked" aria-label="<?php esc_attr_e( 'Remove date', 'resort-booking' ); ?>">&times;</button>
</div>
<?php endforeach; ?>
</div>
	<button type="button" class="button resort-add-blocked"><?php esc_html_e( 'Add blocked date', 'resort-booking' ); ?></button>
	</div>
	<p>
		<label for="resort_adult_price"><strong><?php esc_html_e( 'Default adult price', 'resort-booking' ); ?></strong></label><br />
		<input type="number" step="0.01" min="0" name="resort_adult_price" id="resort_adult_price" value="<?php echo esc_attr( $adult_price ); ?>" />
	</p>
	<p>
		<label for="resort_child_price"><strong><?php esc_html_e( 'Default child price', 'resort-booking' ); ?></strong></label><br />
		<input type="number" step="0.01" min="0" name="resort_child_price" id="resort_child_price" value="<?php echo esc_attr( $child_price ); ?>" />
	</p>
	<?php
	}

/**
 * Save product meta.
 *
 * @param int     $post_id Product ID.
 * @param WP_Post $post    Post object.
 */
public function save_product_meta( $post_id, $post ) {
if ( ! isset( $_POST['resort_booking_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['resort_booking_meta_nonce'] ) ), 'resort_booking_meta' ) ) {
return;
}

if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
return;
}

if ( 'product' !== $post->post_type || ! current_user_can( 'edit_product', $post_id ) ) {
return;
}

	$forced_date = isset( $_POST['resort_forced_date'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_forced_date'] ) ) : '';
	$disable     = isset( $_POST['resort_disable_date_selection'] ) ? '1' : '';
	$blocked     = isset( $_POST['resort_blocked_dates'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['resort_blocked_dates'] ) ) : array();
	$adult_price = isset( $_POST['resort_adult_price'] ) ? floatval( wp_unslash( $_POST['resort_adult_price'] ) ) : '';
	$child_price = isset( $_POST['resort_child_price'] ) ? floatval( wp_unslash( $_POST['resort_child_price'] ) ) : '';

	if ( $forced_date ) {
	update_post_meta( $post_id, '_resort_forced_date', $forced_date );
	} else {
	delete_post_meta( $post_id, '_resort_forced_date' );
}

if ( $disable ) {
update_post_meta( $post_id, '_resort_disable_date_selection', '1' );
} else {
delete_post_meta( $post_id, '_resort_disable_date_selection' );
}

	$blocked = array_filter( array_map( array( $this, 'sanitize_date' ), $blocked ) );
	update_post_meta( $post_id, '_resort_blocked_dates', $blocked );

	if ( '' !== $adult_price ) {
	update_post_meta( $post_id, '_resort_adult_price', $adult_price );
	} else {
	delete_post_meta( $post_id, '_resort_adult_price' );
	}

	if ( '' !== $child_price ) {
	update_post_meta( $post_id, '_resort_child_price', $child_price );
	} else {
	delete_post_meta( $post_id, '_resort_child_price' );
	}
	}

/**
 * Render bulk date assignment page.
 */
public function render_bulk_dates_page() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
return;
}

if ( isset( $_POST['resort_bulk_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['resort_bulk_nonce'] ) ), 'resort_bulk_save' ) ) {
$this->handle_bulk_save();
}
?>
<div class="wrap">
<h1><?php esc_html_e( 'Bulk Booking Date Assignment', 'resort-booking' ); ?></h1>
<form method="post">
<?php wp_nonce_field( 'resort_bulk_save', 'resort_bulk_nonce' ); ?>
<p><?php esc_html_e( 'Paste CSV rows as product_id,date1,date2,...', 'resort-booking' ); ?></p>
<textarea name="resort_bulk_csv" rows="6" style="width:100%;"></textarea>
<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Dates', 'resort-booking' ); ?></button></p>
</form>
</div>
<?php
}

/**
 * Handle bulk save logic.
 */
private function handle_bulk_save() {
$csv = isset( $_POST['resort_bulk_csv'] ) ? wp_unslash( $_POST['resort_bulk_csv'] ) : '';
$lines = array_filter( array_map( 'trim', explode( "\n", $csv ) ) );

foreach ( $lines as $line ) {
$parts      = array_map( 'trim', str_getcsv( $line ) );
$product_id = isset( $parts[0] ) ? absint( $parts[0] ) : 0;
if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
continue;
}

$dates = array();
foreach ( $parts as $index => $value ) {
if ( 0 === $index ) {
continue;
}
$clean = $this->sanitize_date( $value );
if ( $clean ) {
$dates[] = $clean;
}
}
update_post_meta( $product_id, '_resort_blocked_dates', $dates );
}
}

/**
 * Render checkout order page.
 */
public function render_checkout_order_page() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
return;
}

$order = get_option( 'wc_checkout_section_order', array( 'billing', 'shipping', 'order', 'payment' ) );
if ( ! is_array( $order ) ) {
$order = array( 'billing', 'shipping', 'order', 'payment' );
}
?>
<div class="wrap">
<h1><?php esc_html_e( 'Checkout Section Order', 'resort-booking' ); ?></h1>
<p><?php esc_html_e( 'Drag and drop sections to reorder. Save persists via AJAX.', 'resort-booking' ); ?></p>
<ul id="resort-checkout-sortable">
<?php foreach ( $order as $section ) : ?>
<li class="ui-state-default" data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html( ucfirst( $section ) ); ?></li>
<?php endforeach; ?>
</ul>
<button class="button button-primary" id="resort-save-checkout-order" data-nonce="<?php echo esc_attr( wp_create_nonce( 'resort_checkout_order' ) ); ?>"><?php esc_html_e( 'Save Order', 'resort-booking' ); ?></button>
</div>
<?php
}

/**
 * Save checkout section order via AJAX.
 */
public function save_checkout_section_order() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( esc_html__( 'Permission denied', 'resort-booking' ) );
}

$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
if ( ! wp_verify_nonce( $nonce, 'resort_checkout_order' ) ) {
wp_send_json_error( esc_html__( 'Invalid nonce', 'resort-booking' ) );
}

$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
$order = array_map( 'sanitize_text_field', $order );
update_option( 'wc_checkout_section_order', $order );
wp_send_json_success();
}

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current page hook.
 */
public function enqueue_admin_assets( $hook ) {
if ( 'product' === get_post_type() ) {
wp_enqueue_script( 'resort-booking-admin', RESORT_BOOKING_URL . 'assets/js/resort-booking.js', array( 'jquery' ), RESORT_BOOKING_VERSION, true );
}

if ( 'woocommerce_page_resort-checkout-order' === $hook ) {
wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'resort-booking-admin-order', RESORT_BOOKING_URL . 'assets/js/admin-section-order.js', array( 'jquery', 'jquery-ui-sortable' ), RESORT_BOOKING_VERSION, true );
wp_localize_script(
'resort-booking-admin-order',
'resortCheckoutOrder',
array(
'ajaxUrl' => admin_url( 'admin-ajax.php' ),
)
);
}
}

/**
 * Sanitize date value.
 *
 * @param string $date Raw date.
 * @return string
 */
private function sanitize_date( $date ) {
$clean = sanitize_text_field( $date );
return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $clean ) ? $clean : '';
}
}
