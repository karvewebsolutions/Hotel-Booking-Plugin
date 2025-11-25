=== Resort Booking ===
Contributors: openai
Tags: woocommerce, booking, deposit
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Resort Booking adds date selection, deposits, and checkout customization for WooCommerce products.

== Description ==
* Shortcode `[resort_booking product_id="123"]` renders a calendar with Flatpickr.
* Per-product forced dates and blocked dates via metabox.
* Bulk blocked-date assignment page under WooCommerce.
* Checkout fields for booking meta and payment options (full vs deposit).
* Booking fee calculation (sets product price to zero, adds fee for totals).
* AJAX helpers for session persistence and checkout-section ordering.

== Installation ==
1. Upload `resort-booking` to `/wp-content/plugins/`.
2. Activate plugin through the Plugins screen.
3. Configure products with desired/blocked dates and accommodations meta.

== Frequently Asked Questions ==
= How are accommodations set? =
Use custom meta `_resort_accommodations` containing arrays with `name`, `adult`, and `child` keys.

= Does the plugin hide COD for deposits? =
Yes, COD is removed from available gateways when deposit is selected.

== Changelog ==
= 1.1.2 =
* Align checkout payment label with full and deposit radio choices on a single row.
* Refresh checkout totals when payment selection changes so amounts stay in sync.
* Hide the children field when no child pricing is configured to avoid empty inputs.

= 1.1.1 =
* Add default adult/child pricing fields on products and use them in fee calculation.
* Sync checkout session on load so booking charge appears immediately.
* Update booking/checkout styles per supplied design.

= 1.0.0 =
* Initial release.
