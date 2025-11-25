#!/usr/bin/env php
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ABSPATH', '/');

// Mock WordPress environment
$mock_meta = [];

function update_post_meta($post_id, $meta_key, $meta_value) {
    global $mock_meta;
    $mock_meta[$post_id][$meta_key] = $meta_value;
}

function get_post_meta($post_id, $meta_key, $single) {
    global $mock_meta;
    return isset($mock_meta[$post_id][$meta_key]) ? $mock_meta[$post_id][$meta_key] : null;
}

function get_post_type($post_id) {
    return 'product';
}

function absint($val) {
    return intval($val);
}

function wp_unslash($value) {
    return $value;
}

function sanitize_text_field($str) {
    return $str;
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    // No-op for this test
}

function esc_html__($text, $domain) {
    return $text;
}

function esc_attr__($text, $domain) {
    return $text;
}

// Include the class we are testing
require_once dirname(__DIR__) . '/includes/class-resort-admin.php';

class BulkDateAppendTest {
    public function run() {
        echo "Running test: Bulk Date Append\n";

        // Setup
        global $mock_meta;
        $mock_meta = [];
        $product_id = 1;
        $initial_blocked_dates = ['2024-12-25'];
        update_post_meta($product_id, '_resort_blocked_dates', $initial_blocked_dates);

        // The CSV input that will be processed
        $_POST['resort_bulk_csv'] = "$product_id,2025-01-01,2025-01-02";

        // Instantiate the class and call the private method using reflection
        $admin = new Resort_Booking_Admin();
        $reflection = new ReflectionMethod('Resort_Booking_Admin', 'handle_bulk_save');
        $reflection->setAccessible(true);
        $reflection->invoke($admin);

        // Assertions
        $final_blocked_dates = get_post_meta($product_id, '_resort_blocked_dates', true);
        $expected_dates = ['2024-12-25', '2025-01-01', '2025-01-02'];

        if (count($expected_dates) === count($final_blocked_dates) && !array_diff($expected_dates, $final_blocked_dates)) {
            echo "Test passed!\n";
            return 0;
        } else {
            echo "Test failed!\n";
            echo "Expected: " . print_r($expected_dates, true);
            echo "Actual: " . print_r($final_blocked_dates, true);
            return 1;
        }
    }
}

$test = new BulkDateAppendTest();
exit($test->run());
