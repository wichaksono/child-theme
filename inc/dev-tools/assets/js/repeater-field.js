jQuery(document).ready(function($) {
    'use strict';

    /**
     * Initializes all repeater fields on the page.
     */
    function initializeRepeaters() {
        $('.field-repeater').each(function() {
            var $repeater = $(this);
            makeSortable($repeater);
        });
    }

    /**
     * Makes the rows of a repeater sortable.
     * @param {jQuery} $repeater The repeater container.
     */
    function makeSortable($repeater) {
        $repeater.find('.repeater-body').sortable({
            handle: '.repeater-row-handle',
            axis: 'y',
            update: function() {
                updateRowIndexes($repeater);
            }
        });
    }

    /**
     * Updates the name and id attributes of all rows after a sort or add/remove action.
     * @param {jQuery} $repeater The repeater container.
     */
    function updateRowIndexes($repeater) {
        var repeaterName = $repeater.data('repeater-name');

        $repeater.find('.repeater-row').each(function(index) {
            var $row = $(this);
            $row.find('input, textarea, select').each(function() {
                var $field = $(this);
                var fieldName = $field.attr('name');

                if (fieldName) {
                    // Replace the index part of the name, e.g., repeater[0][field] -> repeater[1][field]
                    var newName = fieldName.replace(/\[\d+\]|\[__i__\]/, '[' + index + ']');
                    $field.attr('name', newName);
                }
            });
        });
    }

    /**
     * Handles adding a new repeater row.
     */
    $(document).on('click', '.repeater-add-row', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $repeater = $button.closest('.field-repeater-wrapper').find('.field-repeater');
        var $template = $repeater.siblings('.repeater-template');

        // Get the template HTML
        var templateHtml = $template.html();

        // Get the new index
        var rowIndex = $repeater.find('.repeater-row').length;

        // Replace the placeholder index '__i__' with the actual new index
        templateHtml = templateHtml.replace(/__i__/g, rowIndex);

        // Append the new row
        $repeater.find('.repeater-body').append(templateHtml);
    });

    /**
     * Handles removing a repeater row.
     */
    $(document).on('click', '.repeater-remove-row', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $repeater = $button.closest('.field-repeater');

        // Confirm before deleting
        if (window.confirm('Are you sure you want to remove this row?')) {
            $button.closest('.repeater-row').remove();
            updateRowIndexes($repeater);
        }
    });

    // Initialize all repeaters on page load.
    initializeRepeaters();

});