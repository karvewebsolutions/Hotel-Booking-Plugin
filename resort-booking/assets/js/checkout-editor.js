jQuery(document).ready(function($) {
    var selectedSection = null;

    // Make sections sortable
    $('.resort-checkout-preview').sortable({
        handle: '.resort-drag-handle',
        update: function(event, ui) {
            // Update order numbers
            $('.resort-section-block').each(function(index) {
                $(this).data('order', index + 1);
            });
        }
    });

    // Section click handler
    $(document).on('click', '.resort-section-block', function() {
        $('.resort-section-block').removeClass('selected');
        $(this).addClass('selected');
        selectedSection = $(this).data('section-id');
        loadSectionSettings(selectedSection);
    });

    // Load section settings
    function loadSectionSettings(sectionId) {
        var $section = $('.resort-section-block[data-section-id="' + sectionId + '"]');
        var background = $section.css('background-color') || '#f8f9fa';
        var padding = $section.css('padding') || '20px';
        var visible = $section.is(':visible');

        var template = $('#section-settings-template').html();
        template = template.replace(/<%= title %>/g, sectionId.charAt(0).toUpperCase() + sectionId.slice(1));
        template = template.replace(/<%= background %>/g, rgbToHex(background));
        template = template.replace(/<%= padding %>/g, parseInt(padding));
        template = template.replace(/<%= visible %>/g, visible);

        $('#resort-section-settings').html(template);

        // Initialize color picker
        $('.resort-color-picker').wpColorPicker({
            change: function(event, ui) {
                updateSectionStyle(selectedSection, 'background', ui.color.toString());
            }
        });

        // Padding input
        $('.resort-padding-input').on('input', function() {
            updateSectionStyle(selectedSection, 'padding', $(this).val() + 'px');
        });

        // Visibility toggle
        $('.resort-visibility-toggle').on('change', function() {
            var $section = $('.resort-section-block[data-section-id="' + selectedSection + '"]');
            if ($(this).is(':checked')) {
                $section.show();
            } else {
                $section.hide();
            }
        });
    }

    // Update section style
    function updateSectionStyle(sectionId, property, value) {
        var $section = $('.resort-section-block[data-section-id="' + sectionId + '"]');
        $section.css(property, value);
    }

    // RGB to Hex conversion
    function rgbToHex(rgb) {
        if (rgb.startsWith('#')) return rgb;
        var result = rgb.match(/\d+/g);
        if (!result) return '#f8f9fa';
        return '#' + ((1 << 24) + (parseInt(result[0]) << 16) + (parseInt(result[1]) << 8) + parseInt(result[2])).toString(16).slice(1);
    }

    // Save layout
    $('#resort-save-layout').on('click', function() {
        var layout = {
            sections: []
        };

        $('.resort-section-block').each(function(index) {
            var $section = $(this);
            var sectionId = $section.data('section-id');
            var visible = $section.is(':visible');
            var background = $section.css('background-color');
            var padding = $section.css('padding');

            layout.sections.push({
                id: sectionId,
                order: index + 1,
                visible: visible,
                styles: {
                    background: rgbToHex(background),
                    padding: padding
                }
            });
        });

        $.post(resortCheckoutEditor.ajaxUrl, {
            action: 'resort_save_checkout_layout_v2',
            nonce: '<?php echo wp_create_nonce( 'resort_checkout_layout_v2' ); ?>',
            layout: layout
        }, function(response) {
            if (response.success) {
                alert('Layout saved successfully!');
            } else {
                alert('Error saving layout.');
            }
        });
    });

    // Reset layout
    $('#resort-reset-layout').on('click', function() {
        if (confirm('Are you sure you want to reset to default layout?')) {
            location.reload();
        }
    });

    // Preview (placeholder)
    $('#resort-preview-layout').on('click', function() {
        alert('Preview functionality would open a new window with the checkout page.');
    });
});