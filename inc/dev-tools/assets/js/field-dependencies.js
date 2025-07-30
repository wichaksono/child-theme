/**
 * Field Dependencies Handler
 *
 * This script manages the visibility of form fields based on the values of other fields.
 * It looks for a `data-dependencies` attribute on wrapper elements.
 *
 * @version 1.0.0
 * @author Wichaksono
 */
jQuery(document).ready(function ($) {
    'use strict';

    // A function to check all dependencies on the page.
    function checkAllDependencies() {
        $('[data-dependencies]').each(function () {
            var child = $(this);
            var rules;
            try {
                rules = JSON.parse(child.attr('data-dependencies'));
            } catch (e) {
                console.error('Invalid JSON in data-dependencies attribute for', child);
                return;
            }

            var shouldShow = true;

            // Loop through each dependency rule
            for (var i = 0; i < rules.length; i++) {
                var rule = rules[i];
                var parentField = $('#' + rule.field);
                var parentValue;

                if (parentField.length === 0) {
                    // If the parent field is not found, try finding by name (for radio buttons)
                    parentField = $('[name="' + rule.field + '"]:checked');
                    if (parentField.length === 0) {
                        shouldShow = false;
                        break;
                    }
                }

                // Get the value of the parent field
                if (parentField.is(':checkbox')) {
                    parentValue = parentField.is(':checked') ? parentField.val() : '';
                } else if(parentField.is(':radio')) {
                    parentValue = $('[name="' + rule.field + '"]:checked').val();
                } else {
                    parentValue = parentField.val();
                }

                // Check if the parent value matches the rule
                var valueMatch = rule.value.includes(parentValue);
                var conditionMatch = (rule.condition === '==') ? valueMatch : !valueMatch;

                if (!conditionMatch) {
                    shouldShow = false;
                    break; // No need to check other rules if one fails
                }
            }

            // Show or hide the child element with a slide effect
            if (shouldShow) {
                child.slideDown();
            } else {
                child.slideUp();
            }
        });
    }

    // Attach change event listeners to all relevant form inputs
    $(document).on('change', 'input, select', function() {
        checkAllDependencies();
    });

    // Run on page load to set the initial state
    checkAllDependencies();
});