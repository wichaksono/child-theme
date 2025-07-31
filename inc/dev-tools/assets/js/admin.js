jQuery(document).ready(function ($) {

    /**
     * A robust function to initialize all complex fields within a given container.
     * This can be the whole document on page load, or a new repeater row.
     *
     * @param {jQuery} $container The jQuery object to search within for fields to initialize.
     */
    function initializeDevToolsScripts($container) {
        // --- Initialize Color Pickers ---
        // Target only input fields with the class '.wp-color-picker-field' that have not
        // already been processed by WordPress (which wraps them in a '.wp-picker-container').
        // This is the most reliable way to initialize them.
        const $colorPickers = $container.find('.wp-color-picker-field').filter(function() {
            return $(this).closest('.wp-picker-container').length === 0;
        });

        if ($colorPickers.length > 0 && typeof $.fn.wpColorPicker === 'function') {
            $colorPickers.wpColorPicker();
        }

        // --- Initialize Media Uploader ---
        $container.find('.media-uploader-button').off('click').on('click', function (e) {
            e.preventDefault();
            const wrapper = $(this).closest('.media-uploader-wrapper');
            const idField = wrapper.find('.media-uploader-id');
            const preview = wrapper.find('.media-uploader-preview');
            const remover = wrapper.find('.media-remover-button');

            const frame = wp.media({
                title: 'Select or Upload Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                idField.val(attachment.id);
                // Use the 'medium' size if available, otherwise fallback to 'full'
                const imageUrl = attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                preview.html('<img src="' + imageUrl + '">').show();
                remover.show();
            });

            frame.open();
        });

        // --- Initialize Media Remover ---
        $container.find('.media-remover-button').off('click').on('click', function (e) {
            e.preventDefault();
            const wrapper = $(this).closest('.media-uploader-wrapper');
            wrapper.find('.media-uploader-id').val('');
            wrapper.find('.media-uploader-preview').hide().html('');
            $(this).hide();
        });
    }

    // --- Initial Page Load ---
    // Run the initializer on the whole document when the page is ready.
    initializeDevToolsScripts($(document));


    // --- Repeater Row Added Event Listener ---
    // When our custom event is triggered from repeater-field.js,
    // run the initializer ONLY on the new row.
    $(document).on('neonwebid:repeater-row-added', function (event, newRow) {
        initializeDevToolsScripts($(newRow));
    });

});