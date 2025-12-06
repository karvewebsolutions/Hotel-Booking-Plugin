jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });

    $('.resort-slider').each(function() {
        var $slider = $(this);
        var value = parseInt($slider.data('value'));
        var id = $slider.attr('id');
        var min = 0;
        var max = id === 'section-width-slider' ? 100 : (id === 'option-font-size-slider' ? 24 : 50);
        if (id === 'section-width-slider') min = 50;
        $slider.slider({
            min: min,
            max: max,
            value: value,
            slide: function(event, ui) {
                $('#' + id.replace('-slider', '-value')).text(ui.value);
                updatePreview();
            }
        });
    });

    function updatePreview() {
        var width = $('#section-width-slider').slider('value');
        var padding = $('#section-padding-slider').slider('value');
        var gap = $('#option-gap-slider').slider('value');
        var fontSize = $('#option-font-size-slider').slider('value');
        var height = $('#option-height-slider').slider('value');

        $('.resort-payment-options-preview').css({
            'width': width + '%',
            'padding': padding + 'px'
        });
        $('.resort-payment-group-preview').css('gap', gap + 'px');
        $('.resort-payment-choice-preview').css({
            'font-size': fontSize + 'px',
            'line-height': height + 'px',
            'min-height': height + 'px'
        });
    }

    updatePreview();

    $('#resort-save-sizing').on('click', function() {
        var sizing = {
            section_width: $('#section-width-slider').slider('value'),
            section_padding: $('#section-padding-slider').slider('value'),
            option_gap: $('#option-gap-slider').slider('value'),
            option_font_size: $('#option-font-size-slider').slider('value'),
            option_height: $('#option-height-slider').slider('value')
        };

        $.post(resortPaymentSizing.ajaxUrl, {
            action: 'resort_save_payment_sizing',
            nonce: $(this).data('nonce'),
            sizing: sizing
        }, function(response) {
            if (response.success) {
                alert('Sizing saved successfully!');
            } else {
                alert('Error saving sizing.');
            }
        });
    });
});