<?php
/**
 * Front-end hooks and shortcode.
 *
 * @package ResortBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles shortcode output and assets.
 */
class Resort_Booking_Frontend {
    /**
     * Constructor sets up hooks.
     */
    public function __construct() {
        add_shortcode( 'resort_booking', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue front-end assets.
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), RESORT_BOOKING_VERSION );
        wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), RESORT_BOOKING_VERSION, true );
        wp_enqueue_style( 'resort-booking', RESORT_BOOKING_URL . 'assets/css/resort-booking.css', array(), RESORT_BOOKING_VERSION );
        wp_enqueue_script( 'resort-booking', RESORT_BOOKING_URL . 'assets/js/resort-booking.js', array( 'jquery', 'flatpickr' ), RESORT_BOOKING_VERSION, true );
        wp_localize_script(
            'resort-booking',
            'resortBooking',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'resort_booking' ),
            )
        );
    }

    /**
     * Render booking form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'product_id'    => 0,
                'show_calendar' => 'yes',
                'show_button'   => 'yes',
                'button_label'  => __( 'Book Now', 'resort-booking' ),
            ),
            $atts,
            'resort_booking'
        );

        $product_id        = absint( $atts['product_id'] );
        $show_calendar     = 'no' !== strtolower( $atts['show_calendar'] );
        $show_button       = 'no' !== strtolower( $atts['show_button'] );
        $button_label      = sanitize_text_field( $atts['button_label'] );
        $calendar_required = false;
        $calendar_id       = 'resort-calendar-container-' . absint( $product_id );
        $date_field_id     = 'resort-booking-date-' . absint( $product_id );

        if ( ! $product_id ) {
            return '';
        }

        $forced_date = get_post_meta( $product_id, '_resort_forced_date', true );
        $disable     = get_post_meta( $product_id, '_resort_disable_date_selection', true );
        $blocked     = get_post_meta( $product_id, '_resort_blocked_dates', true );
        $blocked     = is_array( $blocked ) ? $blocked : array();

        if ( ! $show_calendar && ! $forced_date ) {
            $calendar_required = true;
            $show_calendar     = true;
        }

        ob_start();
        ?>
        <form class="resort-booking-form" method="post">
        <?php wp_nonce_field( 'resort_booking_form', 'resort_booking_nonce' ); ?>
        <input type="hidden" name="resort_booking_product_id" value="<?php echo esc_attr( $product_id ); ?>" />

        <?php if ( $disable && $forced_date ) : ?>
        <p class="resort-booking-note"><?php echo esc_html( sprintf( __( 'Booking date fixed to %s', 'resort-booking' ), $forced_date ) ); ?></p>
        <input type="hidden" name="resort_booking_date" value="<?php echo esc_attr( $forced_date ); ?>" />
        <?php else : ?>
            <?php if ( $show_calendar ) : ?>
            <div class="resort-booking-calendar-wrapper">
                <label for="<?php echo esc_attr( $date_field_id ); ?>"><strong><?php esc_html_e( 'Select booking date', 'resort-booking' ); ?></strong></label>
                <input
                        type="text"
                        id="<?php echo esc_attr( $date_field_id ); ?>"
                        class="resort-booking-date"
                        name="resort_booking_date"
                        data-blocked="<?php echo esc_attr( implode( ',', $blocked ) ); ?>"
                        data-calendar-target="<?php echo esc_attr( $calendar_id ); ?>"
                />
                <div id="<?php echo esc_attr( $calendar_id ); ?>" class="resort-inline-calendar"></div>
            </div>
            <?php if ( ! empty( $blocked ) ) : ?>
            <p class="resort-booking-note"><?php esc_html_e( 'Some dates are blocked for this product.', 'resort-booking' ); ?></p>
            <?php endif; ?>
            <?php if ( $calendar_required && ! $forced_date ) : ?>
            <p class="resort-booking-note"><?php esc_html_e( 'Calendar is shown because no forced date is set while the calendar display is disabled.', 'resort-booking' ); ?></p>
            <?php endif; ?>
            <?php elseif ( $forced_date ) : ?>
                <input type="hidden" name="resort_booking_date" value="<?php echo esc_attr( $forced_date ); ?>" />
            <?php endif; ?>
        <?php endif; ?>

        <?php if ( $show_button ) : ?>
        <div class="resort-booking-button-wrapper">
                <button type="submit" class="button resort-booking-submit"><?php echo esc_html( $button_label ); ?></button>
        </div>
        <?php endif; ?>
        </form>
        <?php
        return ob_get_clean();
    }
}
