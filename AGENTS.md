# Resort Booking Plugin - Agent Guidelines

## Quick Start
- **Install**: Copy `resort-booking/` to `wp-content/plugins/` and activate "Resort Booking"
- **Test URL**: Visit product page with booking shortcode to verify functionality
- **Dependencies**: Requires WooCommerce + WordPress 5.0+

## Build, Test & Development Commands

### Single Test Commands
```bash
# Quick syntax check (all PHP files)
find resort-booking -name '*.php' -print0 | xargs -0 -n1 php -l

# Lint with WordPress standards (if phpcs installed)
phpcs --standard=WordPress --ignore=vendor resort-booking

# Run specific test file
php resort-booking/tests/test-bulk-dates.php

# Check plugin activation
wp plugin activate resort-booking --allow-root
```

### Development Workflow
```bash
# Watch for changes and auto-reload
npm run dev  # if package.json exists, or manual file watching

# Clear caches after changes
wp cache flush
wp transient delete --all
```

## Code Style & Standards

### PHP Standards
- **Indentation**: 4 spaces (no tabs)
- **Braces**: Same line: `if ( $condition ) {`
- **Spacing**: Around operators: `$value = $a + $b;`
- **Yoda Conditions**: When applicable: `if ( true === $condition )`
- **Line endings**: LF only, no trailing whitespace
- **Max line length**: 120 characters

### Naming Conventions
- **Classes**: `Resort_Booking_*` (PascalCase)
- **Methods**: `snake_case` (lowercase with underscores)
- **Variables**: `snake_case` (lowercase with underscores)
- **Constants**: `UPPER_SNAKE_CASE` (uppercase with underscores)
- **Hooks**: `resort_` prefix for actions/filters
- **Meta Keys**: `_resort_` prefix for post meta

### Import & File Organization
```php
// Standard header for all files
/**
 * File purpose description.
 *
 * @package ResortBooking
 * @since 1.0.0
 */

// Use WordPress functions only
wp_enqueue_script( $handle, $path, $deps, $ver, $in_footer );
wp_enqueue_style( $handle, $path, $deps, $ver, $media );
```

### Error Handling
```php
// Always validate and sanitize
$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

// Use WordPress error handling
if ( empty( $product_id ) ) {
    wc_add_notice( __( 'Product ID is required.', 'resort-booking' ), 'error' );
    return;
}

// Graceful fallbacks
$accommodations = get_post_meta( $product_id, '_resort_accommodations', true );
$accommodations = is_array( $accommodations ) ? $accommodations : array();
```

### Security & Validation
```php
// Nonce verification
if ( ! isset( $_POST['resort_booking_nonce'] ) 
    || ! wp_verify_nonce( $_POST['resort_booking_nonce'], 'resort_booking_form' ) ) {
    wp_die( 'Security check failed' );
}

// User capability checks
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Insufficient permissions' );
}

// Data sanitization
$adults = isset( $_POST['adults'] ) ? absint( $_POST['adults'] ) : 0;
$accommodation = sanitize_text_field( wp_unslash( $_POST['accommodation'] ) );

// Output escaping
echo esc_html( $user_string );
echo esc_attr( $attribute_value );
```

### Database & Meta Operations
```php
// Use WordPress meta functions
update_post_meta( $post_id, '_resort_accommodations', $accommodations );
$accommodations = get_post_meta( $post_id, '_resort_accommodations', true );

// Session handling (WooCommerce)
WC()->session->set( 'resort_booking_date', $date );
$date = WC()->session->get( 'resort_booking_date' );
```

### JavaScript Standards
```javascript
// Use jQuery if already loaded
(function( $ ) {
    'use strict';
    
    // Your code here
})( jQuery );

// Event delegation
$( document.body ).on( 'change', '.resort-booking-field', function() {
    // Handle dynamic content
});

// AJAX with WordPress patterns
$.post( resortBooking.ajaxUrl, {
    action: 'resort_save_booking_session',
    nonce: resortBooking.nonce,
    data: payload
}, function( response ) {
    if ( response.success ) {
        // Success handling
    }
});
```

### CSS Architecture
```css
/* Use BEM-style naming */
.resort-booking { }
.resort-booking__field { }
.resort-booking--modifier { }

/* Mobile-first responsive */
@media (min-width: 768px) {
    /* Desktop styles */
}

/* WooCommerce integration */
.woocommerce-checkout .resort-booking-field {
    /* Specific checkout overrides */
}
```

## Testing Strategy

### Manual Testing Checklist
- [ ] Booking form submission redirects to checkout
- [ ] Accommodation options display correctly
- [ ] Date picker shows blocked dates
- [ ] Adults/children fields validate properly
- [ ] Payment options (Full/50%) work
- [ ] Cart calculates correct fees
- [ ] Checkout saves all meta data
- [ ] Thank you page shows balance
- [ ] Admin settings save correctly
- [ ] Bulk date import works

### Cross-Browser Testing
- Chrome (latest)
- Firefox (latest) 
- Safari (latest)
- Edge (latest)

### WordPress Version Testing
- WordPress 5.0+ (minimum supported)
- WooCommerce 5.0+ (minimum supported)
- PHP 7.4+ (minimum supported)

## Key Architecture Patterns

### Class Structure
```php
class Resort_Booking_Component {
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }
    
    public function init() {
        // Initialization logic
    }
    
    private function helper_method() {
        // Internal utilities
    }
}
```

### Hook Integration
```php
// WooCommerce hooks
add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout' ) );
add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price' ) );

// WordPress hooks
add_action( 'init', array( $this, 'register_post_type' ) );
add_filter( 'the_content', array( $this, 'filter_content' ) );
```

## Common Issues & Solutions

### Booking Form Not Submitting
- **Cause**: Missing nonce or wrong hook priority
- **Fix**: Use `template_redirect` hook, verify nonce exists

### AJAX Not Working
- **Cause**: Missing action hooks or wrong data format
- **Fix**: Register both `wp_ajax_*` and `wp_ajax_nopriv_*` actions

### Styles Not Loading
- **Cause**: Wrong dependency order or missing wp_enqueue_scripts action
- **Fix**: Check hook priority, use proper dependencies

### Session Data Lost
- **Cause**: WooCommerce cart cleared or session timeout
- **Fix**: Persist data immediately, use WC()->session properly

## Performance Guidelines

### Database Queries
- Use `WP_Query` with proper parameters
- Cache expensive operations with `wp_cache_get/set`
- Avoid `SELECT *` - specify exact columns needed

### Asset Loading
- Load scripts only on required pages: `is_checkout()`, `is_product()`
- Minify assets in production
- Use WordPress script localization

## Debugging Tools

### WordPress Debug
```php
// Enable in wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Debug output
error_log( 'Resort Booking: ' . $message );
wc_add_notice( $message, 'notice' ); // For admin visibility
```

### Browser Console
```javascript
// Debug logging
console.log( 'Resort Booking:', data );

// AJAX debugging
$.ajaxSetup({
    error: function( xhr, status, error ) {
        console.error( 'AJAX Error:', error );
    }
});
```

## Deployment Notes

### Version Bumping
1. Update `resort-booking.php` header version
2. Update `readme.txt` stable tag
3. Add changelog entry to README.md
4. Commit with version number

### Production Checklist
- [ ] Run `phpcs` linting
- [ ] Test all user workflows
- [ ] Verify no debug code left
- [ ] Check all translations are proper
- [ ] Confirm assets are minified (if applicable)