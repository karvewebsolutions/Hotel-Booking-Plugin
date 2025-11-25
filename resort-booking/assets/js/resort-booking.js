(function( $ ) {
'use strict';

var config = window.resortBooking || { ajaxUrl: ( window.ajaxurl || '' ), nonce: '' };

function initDatepicker() {
var $field = $( '#resort-booking-date' );
if ( ! $field.length || 'function' !== typeof flatpickr ) {
return;
}
var blocked = $field.data( 'blocked' ) ? $field.data( 'blocked' ).toString().split( ',' ) : [];
flatpickr( $field[0], {
altInput: true,
dateFormat: 'Y-m-d',
disable: blocked,
} );
}

function syncToSession() {
var $childrenField = $( '#resort_booking_children' );
var childrenValue = $childrenField.length ? $childrenField.val() : 0;

var adultsValue = $( '#resort_booking_adults' ).val() || 0;

var payload = {
action: 'resort_save_booking_session',
nonce: resortBooking.nonce,
date: $( 'input[name="resort_booking_date"]' ).val(),
accommodation: $( '#resort_booking_accommodation' ).val(),
adults: adultsValue,
children: childrenValue,
payment: $( 'input[name="resort_payment_option"]:checked' ).val(),
};

$.post( config.ajaxUrl, payload, function() {
$( document.body ).trigger( 'update_checkout' );
reloadSummary();
} );
}

function triggerOnLoad() {
// Trigger one sync on first paint to ensure fees appear even before user edits fields.
syncToSession();
}

function reloadSummary() {
$.post( config.ajaxUrl, { action: 'reload_booking_summary' }, function( html ) {
$( '.resort-booking-checkout' ).find( '.resort-summary' ).remove();
$( '.resort-booking-checkout' ).append( html );
} );
}

function adminBlockedDates() {
$( '#resort-blocked-dates-wrapper' ).on( 'click', '.resort-add-blocked', function() {
$( this ).closest( '#resort-blocked-dates-wrapper' ).find( '.resort-blocked-repeat' ).append( '<div class="resort-blocked-row"><input type="date" name="resort_blocked_dates[]" /><button type="button" class="button resort-remove-blocked" aria-label="Remove date">&times;</button></div>' );
} );

$( '#resort-blocked-dates-wrapper' ).on( 'click', '.resort-remove-blocked', function() {
$( this ).closest( '.resort-blocked-row' ).remove();
} );
}

$( function() {
initDatepicker();
$( document.body ).on( 'input change', '#resort_booking_accommodation, #resort_booking_adults, #resort_booking_children, input[name="resort_payment_option"]', syncToSession );
$( document.body ).on( 'updated_checkout', reloadSummary );
if ( $( '.resort-booking-checkout' ).length ) {
triggerOnLoad();
}
adminBlockedDates();
} );
})( jQuery );
