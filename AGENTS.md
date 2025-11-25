# Repository Guidelines

## Project Structure & Module Organization
- Root plugin lives in `resort-booking/`; activate by placing that folder in `wp-content/plugins/` of a WooCommerce-enabled site.
- Entry point `resort-booking.php` registers hooks and loads classes from `includes/`.
- Business logic sits in `includes/` (`class-resort-booking.php`, `class-resort-admin.php`, `class-resort-frontend.php`, `class-resort-wc.php`), separated by concern.
- Client assets are under `assets/` (`css/resort-booking.css`, `js/resort-booking.js`, `js/admin-section-order.js`); keep third-party libraries checked in here.
- Documentation for admins lives in `README.md` and `readme.txt` within `resort-booking/`.

## Build, Test, and Development Commands
- Install locally by copying `resort-booking/` into a WordPress `wp-content/plugins/` directory and activating **Resort Booking**.
- Quick syntax check: `find resort-booking -name '*.php' -print0 | xargs -0 -n1 php -l`.
- Optional lint with WordPress standards if you have `phpcs` installed: `phpcs --standard=WordPress --ignore=vendor resort-booking`.
- Current plugin version: 1.1.1 (update `resort-booking.php` header + `readme.txt` stable tag + changelog when bumping).
- Pricing assumption: product base price can stay zero; booking charges come from accommodations or the `_resort_adult_price` / `_resort_child_price` product meta.
- To test UI/flows, spin up WordPress (e.g., `wp-env start` or a local LAMP stack) with WooCommerce active, then exercise the booking shortcode and admin pages.

## Coding Style & Naming Conventions
- Follow WordPress PHP standards: 4-space indentation, braces on the same line, Yoda conditions where applicable, and spacing around operators.
- Escape output (`esc_html__`, `esc_attr__`) and sanitize input (`sanitize_text_field`, `wp_unslash`) consistently; mirror existing patterns in `class-resort-admin.php`.
- Prefix hooks, functions, and meta keys with `resort_`; classes follow `Resort_Booking_*`.
- Keep translations loaded via the `resort-booking` text domain for any new user-facing strings.

## Testing Guidelines
- No automated test suite is present; rely on manual verification in a WooCommerce store.
- Validate flows in README: booking with forced/blocked dates, checkout submission (adults/children counts), fee calculation for full vs deposit, thank-you page balance, COD hidden on deposit, admin bulk CSV import, and checkout section drag/drop order.
- When fixing bugs, add minimal regression steps to PR descriptions; if you add tooling, document how to run it.

## Commit & Pull Request Guidelines
- Use short, imperative commit subjects (e.g., `Add blocked date validation`); include context in the body only when needed.
- For PRs, link related issues, describe behavior changes, and attach screenshots/GIFs for UI/admin updates (metaboxes, settings pages).
- Note environment used for verification (WordPress + WooCommerce versions, browser). Mark any known gaps or manual checks not run.

## Security & Configuration Tips
- Do not commit secrets; rely on WordPress/WooCommerce config files outside the repo.
- Always validate and sanitize incoming `$_POST`/`$_GET` data and escape all output rendered in admin pages or shortcodes.
- Keep compatibility in mind: avoid functions requiring newer PHP/WP than the plugin already supports; test against the minimum WooCommerce version you target.
