# Resort Booking

Resort Booking is a WooCommerce extension that provides date-based bookings with deposit handling, forced dates, and checkout customization.

## Version
- Current release: **1.1.5**
- Highlights: three Elementor widgets (button, calendar, or both) with alignment/typography controls, a button widget date selector that respects product forced dates, and calendar-only auto-submit to checkout.

## Installation
1. Copy the `resort-booking` folder into `wp-content/plugins/`.
2. Activate **Resort Booking** in WordPress admin.
3. Ensure WooCommerce is active.

## Usage
- Add shortcode `[resort_booking product_id="123"]` on a page. Customers can select dates unless a product is configured with a forced date.
- Edit a product to set **Desired booking date**, **Disable date picker**, and **Blocked dates**.
- Use **WooCommerce > Resort Booking Dates** to bulk paste CSV rows `product_id,date1,date2` for blocked dates.
- Use **WooCommerce > Checkout Section Order** to reorder sections; drag the list and click Save.

## Admin settings locations
- **Bulk blocked dates:** In the WordPress dashboard go to **WooCommerce → Resort Booking Dates**.
- **Checkout section order:** In the WordPress dashboard go to **WooCommerce → Checkout Section Order**.
- **Per-product booking settings:** Edit an individual product and use the **Resort Booking Dates** metabox in the sidebar to set a forced date, disable the date picker, or add blocked dates.

## Checkout fields
- Booking date (readonly)
- Accommodation selector from `_resort_accommodations` meta
- Adults/Children counts
- Payment option: full or 50% deposit

## Pricing
The product price is displayed as zero during booking; a booking fee equal to the calculated total (or 50% for deposit) is added. Taxes/coupons apply to the fee. Reports that rely on product price should be validated.

## Sample accommodations meta
```
$accommodations = [
  [
    'name'  => 'Deluxe Room',
    'adult' => 1500.00,
    'child' => 750.00,
  ],
  [
    'name'  => 'Family Suite',
    'adult' => 2500.00,
    'child' => 1250.00,
  ],
];
update_post_meta( $product_id, '_resort_accommodations', $accommodations );
```

## Testing checklist
- Booking form with forced/blocked dates
- Checkout submission with booking fields
- AJAX session save (`resort_save_booking_session`)
- Fee calculation for full vs deposit; COD hidden for deposit
- Remaining balance appears on thank-you page and emails
- Admin bulk date import
