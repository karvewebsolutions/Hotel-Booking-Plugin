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
	add_action( 'wp_ajax_resort_save_checkout_layout', array( $this, 'save_checkout_layout' ) );
	add_action( 'wp_ajax_resort_save_payment_sizing', array( $this, 'save_payment_sizing' ) );
	add_action( 'wp_ajax_resort_save_checkout_layout_v2', array( $this, 'save_checkout_layout_v2' ) );
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

	add_submenu_page(
		'woocommerce',
		esc_html__( 'Checkout Layout Editor', 'resort-booking' ),
		esc_html__( 'Checkout Layout Editor', 'resort-booking' ),
		'manage_woocommerce',
		'resort-checkout-layout',
		array( $this, 'render_checkout_layout_page' )
	);

	add_submenu_page(
		'woocommerce',
		esc_html__( 'Payment Options', 'resort-booking' ),
		esc_html__( 'Payment Options', 'resort-booking' ),
		'manage_woocommerce',
		'resort-payment-options',
		array( $this, 'render_payment_options_page' )
	);

	add_submenu_page(
		'woocommerce',
		esc_html__( 'Checkout Layout Editor', 'resort-booking' ),
		esc_html__( 'Checkout Layout Editor', 'resort-booking' ),
		'manage_woocommerce',
		'resort-checkout-editor',
		array( $this, 'render_checkout_editor_page' )
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

$existing_dates = get_post_meta( $product_id, '_resort_blocked_dates', true );
if ( ! is_array( $existing_dates ) ) {
	$existing_dates = array();
}

$new_dates = array();
foreach ( $parts as $index => $value ) {
	if ( 0 === $index ) {
		continue;
	}
	$clean = $this->sanitize_date( $value );
	if ( $clean ) {
		$new_dates[] = $clean;
	}
}

$all_dates = array_values( array_unique( array_merge( $existing_dates, $new_dates ) ) );
update_post_meta( $product_id, '_resort_blocked_dates', $all_dates );
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
 * Render checkout layout editor page.
 */
public function render_checkout_layout_page() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
return;
}

$layout = get_option( 'resort_checkout_layout', array() );
$checkout_fields = WC()->checkout->get_checkout_fields();
$sections = array( 'billing', 'shipping', 'order', 'additional' );
?>
<div class="wrap">
<h1><?php esc_html_e( 'Checkout Layout Editor', 'resort-booking' ); ?></h1>
<p><?php esc_html_e( 'Drag and drop fields to reorder within sections. Uncheck to hide fields. Save persists via AJAX.', 'resort-booking' ); ?></p>
<div id="resort-layout-editor">
<?php foreach ( $sections as $section ) : ?>
<div class="resort-section" data-section="<?php echo esc_attr( $section ); ?>">
<h3><?php echo esc_html( ucfirst( $section ) ); ?> Fields</h3>
<ul class="resort-field-sortable">
<?php
$fields = isset( $checkout_fields[$section] ) ? $checkout_fields[$section] : array();
$enabled = isset( $layout[$section]['enabled'] ) ? $layout[$section]['enabled'] : array_keys( $fields );
$order = isset( $layout[$section]['order'] ) ? $layout[$section]['order'] : array_keys( $fields );

foreach ( $order as $field_key ) {
if ( ! isset( $fields[$field_key] ) ) continue;
$field = $fields[$field_key];
$checked = in_array( $field_key, $enabled ) ? 'checked' : '';
?>
<li class="resort-field-item" data-field="<?php echo esc_attr( $field_key ); ?>">
<label>
<input type="checkbox" class="resort-field-toggle" <?php echo esc_attr( $checked ); ?> />
<span class="resort-field-label"><?php echo esc_html( $field['label'] ); ?> (<?php echo esc_html( $field_key ); ?>)</span>
</label>
</li>
<?php } ?>
</ul>
</div>
<?php endforeach; ?>
</div>
<button class="button button-primary" id="resort-save-layout" data-nonce="<?php echo esc_attr( wp_create_nonce( 'resort_checkout_layout' ) ); ?>"><?php esc_html_e( 'Save Layout', 'resort-booking' ); ?></button>
</div>
<?php
}

/**
 * Save checkout layout via AJAX.
 */
public function save_checkout_layout() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( esc_html__( 'Permission denied', 'resort-booking' ) );
}

$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
if ( ! wp_verify_nonce( $nonce, 'resort_checkout_layout' ) ) {
wp_send_json_error( esc_html__( 'Invalid nonce', 'resort-booking' ) );
}

$layout = isset( $_POST['layout'] ) ? (array) wp_unslash( $_POST['layout'] ) : array();
$layout = array_map( function( $section ) {
return array(
'order' => array_map( 'sanitize_text_field', $section['order'] ?? array() ),
'enabled' => array_map( 'sanitize_text_field', $section['enabled'] ?? array() ),
);
}, $layout );
update_option( 'resort_checkout_layout', $layout );
wp_send_json_success();
}

/**
 * Render payment options page.
 */
public function render_payment_options_page() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
return;
}

if ( isset( $_POST['resort_payment_options_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['resort_payment_options_nonce'] ) ), 'resort_payment_options_save' ) ) {
$this->save_payment_options();
echo '<div class="notice notice-success"><p>' . esc_html__( 'Payment options saved.', 'resort-booking' ) . '</p></div>';
}

$full_label = get_option( 'resort_payment_full_label', __( 'Pay Full Amount', 'resort-booking' ) );
$deposit_label = get_option( 'resort_payment_deposit_label', __( 'Pay 50% Deposit', 'resort-booking' ) );
$deposit_percentage = get_option( 'resort_deposit_percentage', 50 );
$sizing = get_option( 'resort_payment_sizing', array(
	'section_width' => 100,
	'section_padding' => 10,
	'option_gap' => 12,
	'option_font_size' => 16,
	'option_height' => 30,
) );
?>
<div class="wrap">
<h1><?php esc_html_e( 'Payment Options', 'resort-booking' ); ?></h1>

<h2 class="nav-tab-wrapper">
<a href="#labels" class="nav-tab nav-tab-active"><?php esc_html_e( 'Labels & Percentage', 'resort-booking' ); ?></a>
<a href="#sizing" class="nav-tab"><?php esc_html_e( 'Sizing & Layout', 'resort-booking' ); ?></a>
</h2>

<div id="labels" class="tab-content">
<form method="post">
<?php wp_nonce_field( 'resort_payment_options_save', 'resort_payment_options_nonce' ); ?>
<table class="form-table">
<tr>
<th scope="row"><?php esc_html_e( 'Full Payment Label', 'resort-booking' ); ?></th>
<td><input type="text" name="resort_payment_full_label" value="<?php echo esc_attr( $full_label ); ?>" class="regular-text" /></td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Deposit Payment Label', 'resort-booking' ); ?></th>
<td><input type="text" name="resort_payment_deposit_label" value="<?php echo esc_attr( $deposit_label ); ?>" class="regular-text" /></td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Deposit Percentage', 'resort-booking' ); ?></th>
<td><input type="number" min="1" max="99" name="resort_deposit_percentage" value="<?php echo esc_attr( $deposit_percentage ); ?>" />%</td>
</tr>
</table>
<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Options', 'resort-booking' ); ?></button></p>
</form>
</div>

<div id="sizing" class="tab-content" style="display:none;">
<div class="resort-sizing-editor">
<div class="resort-preview-section">
<h3><?php esc_html_e( 'Live Preview', 'resort-booking' ); ?></h3>
<div class="resort-payment-preview">
<div class="resort-payment-options-preview">
<span class="resort-payment-label-preview"><?php esc_html_e( 'Payment option', 'resort-booking' ); ?></span>
<span class="resort-payment-group-preview">
<label class="resort-payment-choice-preview"><input type="radio" name="preview_payment" value="full" checked /> <?php echo esc_html( $full_label ); ?></label>
<label class="resort-payment-choice-preview"><input type="radio" name="preview_payment" value="deposit" /> <?php echo esc_html( $deposit_label ); ?></label>
</span>
</div>
</div>
</div>

<div class="resort-controls-section">
<h3><?php esc_html_e( 'Sizing Controls', 'resort-booking' ); ?></h3>
<div class="resort-slider-group">
<label><?php esc_html_e( 'Section Width (%)', 'resort-booking' ); ?>: <span id="section-width-value"><?php echo esc_html( $sizing['section_width'] ); ?></span></label>
<div id="section-width-slider" class="resort-slider" data-value="<?php echo esc_attr( $sizing['section_width'] ); ?>"></div>
</div>
<div class="resort-slider-group">
<label><?php esc_html_e( 'Section Padding (px)', 'resort-booking' ); ?>: <span id="section-padding-value"><?php echo esc_html( $sizing['section_padding'] ); ?></span></label>
<div id="section-padding-slider" class="resort-slider" data-value="<?php echo esc_attr( $sizing['section_padding'] ); ?>"></div>
</div>
<div class="resort-slider-group">
<label><?php esc_html_e( 'Option Gap (px)', 'resort-booking' ); ?>: <span id="option-gap-value"><?php echo esc_html( $sizing['option_gap'] ); ?></span></label>
<div id="option-gap-slider" class="resort-slider" data-value="<?php echo esc_attr( $sizing['option_gap'] ); ?>"></div>
</div>
<div class="resort-slider-group">
<label><?php esc_html_e( 'Option Font Size (px)', 'resort-booking' ); ?>: <span id="option-font-size-value"><?php echo esc_html( $sizing['option_font_size'] ); ?></span></label>
<div id="option-font-size-slider" class="resort-slider" data-value="<?php echo esc_attr( $sizing['option_font_size'] ); ?>"></div>
</div>
<div class="resort-slider-group">
<label><?php esc_html_e( 'Option Height (px)', 'resort-booking' ); ?>: <span id="option-height-value"><?php echo esc_html( $sizing['option_height'] ); ?></span></label>
<div id="option-height-slider" class="resort-slider" data-value="<?php echo esc_attr( $sizing['option_height'] ); ?>"></div>
</div>
<button id="resort-save-sizing" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'resort_payment_sizing' ) ); ?>"><?php esc_html_e( 'Save Sizing', 'resort-booking' ); ?></button>
</div>
</div>
</div>


</div>
<?php
}

/**
 * Save payment options.
 */
private function save_payment_options() {
$full_label = isset( $_POST['resort_payment_full_label'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_payment_full_label'] ) ) : '';
$deposit_label = isset( $_POST['resort_payment_deposit_label'] ) ? sanitize_text_field( wp_unslash( $_POST['resort_payment_deposit_label'] ) ) : '';
$percentage = isset( $_POST['resort_deposit_percentage'] ) ? absint( $_POST['resort_deposit_percentage'] ) : 50;

update_option( 'resort_payment_full_label', $full_label );
update_option( 'resort_payment_deposit_label', $deposit_label );
update_option( 'resort_deposit_percentage', $percentage );
}

/**
 * Save payment sizing via AJAX.
 */
public function save_payment_sizing() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( esc_html__( 'Permission denied', 'resort-booking' ) );
}

$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
if ( ! wp_verify_nonce( $nonce, 'resort_payment_sizing' ) ) {
wp_send_json_error( esc_html__( 'Invalid nonce', 'resort-booking' ) );
}

$sizing = isset( $_POST['sizing'] ) ? (array) wp_unslash( $_POST['sizing'] ) : array();
$sizing = array_map( 'absint', $sizing );
update_option( 'resort_payment_sizing', $sizing );
wp_send_json_success();
}

/**
 * Render checkout editor page.
 */
public function render_checkout_editor_page() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
return;
}

$layout = get_option( 'resort_checkout_layout_v2', array(
'version' => 1,
'sections' => array(
array('id' => 'billing', 'order' => 1, 'visible' => true, 'styles' => array('background' => '#f8f9fa', 'padding' => '20px')),
array('id' => 'shipping', 'order' => 2, 'visible' => true, 'styles' => array('background' => '#f8f9fa', 'padding' => '20px')),
array('id' => 'order', 'order' => 3, 'visible' => true, 'styles' => array('background' => '#f8f9fa', 'padding' => '20px')),
array('id' => 'payment', 'order' => 4, 'visible' => true, 'styles' => array('background' => '#f8f9fa', 'padding' => '20px')),
)
));
?>
<div class="wrap">
<h1><?php esc_html_e( 'Checkout Layout Editor', 'resort-booking' ); ?></h1>
<div class="resort-editor-header">
<button id="resort-save-layout" class="button button-primary"><?php esc_html_e( 'Save Layout', 'resort-booking' ); ?></button>
<button id="resort-preview-layout" class="button"><?php esc_html_e( 'Preview', 'resort-booking' ); ?></button>
<button id="resort-reset-layout" class="button"><?php esc_html_e( 'Reset to Default', 'resort-booking' ); ?></button>
</div>

<div class="resort-editor-container">
<div class="resort-editor-canvas">
<div class="resort-checkout-preview">
<?php foreach ( $layout['sections'] as $section ) : ?>
<div class="resort-section-block" data-section-id="<?php echo esc_attr( $section['id'] ); ?>" style="background: <?php echo esc_attr( $section['styles']['background'] ?? '#f8f9fa' ); ?>; padding: <?php echo esc_attr( $section['styles']['padding'] ?? '20px' ); ?>;">
<div class="resort-section-header">
<h4><?php echo esc_html( ucfirst( $section['id'] ) ); ?> Section</h4>
<span class="resort-drag-handle">⋮⋮</span>
</div>
<div class="resort-section-content">
<p><?php echo esc_html( ucfirst( $section['id'] ) ); ?> fields will appear here.</p>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="resort-editor-sidebar">
<div class="resort-sidebar-panel">
<h3><?php esc_html_e( 'Section Settings', 'resort-booking' ); ?></h3>
<div id="resort-section-settings">
<p><?php esc_html_e( 'Click on a section to edit its settings.', 'resort-booking' ); ?></p>
</div>
</div>
</div>
</div>
</div>

<script type="text/template" id="section-settings-template">
<div class="resort-settings-form">
<h4><%= title %> Settings</h4>
<div class="resort-setting-group">
<label><?php esc_html_e( 'Background Color', 'resort-booking' ); ?></label>
<input type="text" class="resort-color-picker" value="<%= background %>" />
</div>
<div class="resort-setting-group">
<label><?php esc_html_e( 'Padding (px)', 'resort-booking' ); ?></label>
<input type="number" class="resort-padding-input" value="<%= padding %>" min="0" max="100" />
</div>
<div class="resort-setting-group">
<label><?php esc_html_e( 'Visible', 'resort-booking' ); ?></label>
<input type="checkbox" class="resort-visibility-toggle" <%= visible ? 'checked' : '' %> />
</div>
</div>
</script>
<?php
}

/**
 * Save checkout layout v2 via AJAX.
 */
public function save_checkout_layout_v2() {
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( esc_html__( 'Permission denied', 'resort-booking' ) );
}

$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
if ( ! wp_verify_nonce( $nonce, 'resort_checkout_layout_v2' ) ) {
wp_send_json_error( esc_html__( 'Invalid nonce', 'resort-booking' ) );
}

$layout = isset( $_POST['layout'] ) ? (array) wp_unslash( $_POST['layout'] ) : array();
$layout = array(
'version' => 1,
'sections' => array_map( function( $section ) {
return array(
'id' => sanitize_text_field( $section['id'] ),
'order' => absint( $section['order'] ),
'visible' => (bool) $section['visible'],
'styles' => array(
'background' => sanitize_hex_color( $section['styles']['background'] ?? '#f8f9fa' ),
'padding' => sanitize_text_field( $section['styles']['padding'] ?? '20px' ),
),
);
}, $layout['sections'] ?? array() )
);
update_option( 'resort_checkout_layout_v2', $layout );
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

	if ( 'woocommerce_page_resort-checkout-layout' === $hook ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'resort-booking-admin-layout', RESORT_BOOKING_URL . 'assets/js/admin-layout-editor.js', array( 'jquery', 'jquery-ui-sortable' ), RESORT_BOOKING_VERSION, true );
		wp_localize_script(
			'resort-booking-admin-layout',
			'resortCheckoutLayout',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	if ( 'woocommerce_page_resort-payment-options' === $hook ) {
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-resizable' );
		wp_enqueue_script( 'resort-booking-admin-sizing', RESORT_BOOKING_URL . 'assets/js/admin-sizing-editor.js', array( 'jquery', 'jquery-ui-slider', 'jquery-ui-resizable' ), RESORT_BOOKING_VERSION, true );
		wp_localize_script(
			'resort-booking-admin-sizing',
			'resortPaymentSizing',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	if ( 'woocommerce_page_resort-checkout-editor' === $hook ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'resort-booking-checkout-editor', RESORT_BOOKING_URL . 'assets/js/checkout-editor.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'wp-color-picker' ), RESORT_BOOKING_VERSION, true );
		wp_localize_script(
			'resort-booking-checkout-editor',
			'resortCheckoutEditor',
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
