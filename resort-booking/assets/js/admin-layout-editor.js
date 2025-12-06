jQuery(document).ready(function($) {
    $('.resort-field-sortable').sortable({
        placeholder: 'resort-field-placeholder',
        update: function(event, ui) {
            // Optional: update order on drag
        }
    });

    $('#resort-save-layout').on('click', function() {
        var layout = {};
        $('.resort-section').each(function() {
            var section = $(this).data('section');
            var order = [];
            var enabled = [];
            $(this).find('.resort-field-item').each(function() {
                var field = $(this).data('field');
                order.push(field);
                if ($(this).find('.resort-field-toggle').is(':checked')) {
                    enabled.push(field);
                }
            });
            layout[section] = {
                order: order,
                enabled: enabled
            };
        });

        $.post(resortCheckoutLayout.ajaxUrl, {
            action: 'resort_save_checkout_layout',
            nonce: $(this).data('nonce'),
            layout: layout
        }, function(response) {
            if (response.success) {
                alert('Layout saved successfully!');
            } else {
                alert('Error saving layout.');
            }
        });
    });
});