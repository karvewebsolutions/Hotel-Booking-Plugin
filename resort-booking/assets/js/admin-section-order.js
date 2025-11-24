(function( $ ) {
'use strict';
$( function() {
$( '#resort-checkout-sortable' ).sortable();
$( '#resort-save-checkout-order' ).on( 'click', function( e ) {
e.preventDefault();
var order = [];
$( '#resort-checkout-sortable' ).find( 'li' ).each( function() {
order.push( $( this ).data( 'section' ) );
} );
$.post(
ajaxurl,
{
action: 'resort_save_checkout_section_order',
nonce: $( this ).data( 'nonce' ),
order: order,
},
function() {
alert( 'Saved' );
}
);
} );
} );
})( jQuery );
