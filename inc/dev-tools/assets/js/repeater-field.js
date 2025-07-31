jQuery(document).ready(function ($) {
    function initializeRepeater($repeater) {
        const $body = $repeater.find('.repeater-body');
        const name = $repeater.data('repeater-name');
        const $template = $repeater.find('.repeater-template').first();

        // Make rows sortable
        $body.sortable({
            handle: '.repeater-row-handle',
            placeholder: 'repeater-row-placeholder',
            forcePlaceholderSize: true,
            update: function () {
                updateIndices();
            }
        });

        // Add Row
        $repeater.find('.repeater-add-row').on('click', function () {
            const index = $body.children('.repeater-row').length;
            let newRowHtml = $template.html().replace(/__i__/g, index);
            const $newRow = $(newRowHtml).appendTo($body);

            // Trigger custom event for other scripts (like admin.js)
            $(document).trigger('neonwebid:repeater-row-added', [$newRow]);
            updateIndices();
        });

        // Remove Row
        $body.on('click', '.repeater-remove-row', function () {
            $(this).closest('.repeater-row').remove();
            updateIndices();
        });

        // --- NEW: Collapse/Expand Logic ---
        $body.on('click', '.repeater-row-header', function(e) {
            // Prevent toggling when clicking on the remove button
            if ($(e.target).is('.repeater-remove-row')) {
                return;
            }
            $(this).closest('.repeater-row').toggleClass('is-collapsed');
        });

        // --- NEW: Dynamic Title Update ---
        $body.on('input', '.repeater-row-content .field-wrapper:first-of-type input[type="text"]', function() {
            const $row = $(this).closest('.repeater-row');
            const newTitle = $(this).val() || 'New Item';
            $row.find('.repeater-row-title').text(newTitle);
        });

        function updateIndices() {
            $body.children('.repeater-row').each(function (i) {
                $(this).find('[name]').each(function () {
                    this.name = this.name.replace(new RegExp(name + '\\[\\d+\\]'), name + '[' + i + ']');
                    this.id = this.id.replace(new RegExp(name + '-\\d+-'), name + '-' + i + '-');
                });
            });
        }
    }

    // Initialize all repeaters on the page
    $('.field-repeater').each(function () {
        initializeRepeater($(this));
    });
});