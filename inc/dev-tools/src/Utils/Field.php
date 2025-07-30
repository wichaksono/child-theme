<?php
namespace NeonWebId\DevTools\Utils;

use function absint;
use function checked;
use function esc_attr;
use function esc_html;
use function esc_url;
use function json_encode;
use function plugin_dir_url;
use function printf;
use function selected;
use function sprintf;
use function wp_editor;
use function wp_enqueue_script;
use function wp_get_attachment_image_url;
use function wp_kses_post;

/**
 * Class Field
 *
 * A factory class for generating a wide range of HTML form fields for the WordPress admin area.
 * It integrates with the DevOption class to automatically populate field values
 * and supports dependencies, repeaters, and other advanced field types.
 *
 * @author Wichaksono
 * @date   2025-07-30
 */
final class Field
{
    /**
     * The DevOption instance for retrieving saved values.
     * @var DevOption
     */
    private DevOption $option;

    /**
     * Tracks if repeater assets have been enqueued to prevent duplicate loading.
     * @var bool
     */
    private static bool $repeater_assets_enqueued = false;

    /**
     * Field constructor.
     *
     * @param DevOption $option The dependency for getting and setting option values.
     */
    public function __construct(DevOption $option)
    {
        $this->option = $option;
    }

    /**
     * Renders a standard text input field.
     *
     * @param string $name The name attribute for the input, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $args Optional arguments: 'default', 'class', 'placeholder', 'description', 'dependencies', 'sanitize' (bool, default true).
     * @return void
     */
    public function text(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? '');
        $class = $args['class'] ?? 'regular-text';

        // Allow disabling sanitization for raw HTML/JS. Default is true.
        $should_sanitize = $args['sanitize'] ?? true;
        $display_value = $should_sanitize ? esc_attr($value) : $value;

        $inputHtml = sprintf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" placeholder="%4$s">',
            esc_attr($name),
            $display_value, // Use potentially unsanitized value
            esc_attr($class),
            esc_attr($args['placeholder'] ?? '')
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a number input field.
     *
     * @param string $name The name attribute for the input, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $args Optional arguments: 'default', 'class', 'placeholder', 'description', 'min', 'max', 'step', 'dependencies'.
     * @return void
     */
    public function number(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? '');
        $class = $args['class'] ?? 'small-text';

        $min_attr = isset($args['min']) ? sprintf('min="%s"', esc_attr($args['min'])) : '';
        $max_attr = isset($args['max']) ? sprintf('max="%s"', esc_attr($args['max'])) : '';
        $step_attr = isset($args['step']) ? sprintf('step="%s"', esc_attr($args['step'])) : '';

        $inputHtml = sprintf(
            '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="%3$s" placeholder="%4$s" %5$s %6$s %7$s>',
            esc_attr($name),
            esc_attr($value),
            esc_attr($class),
            esc_attr($args['placeholder'] ?? ''),
            $min_attr,
            $max_attr,
            $step_attr
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a textarea field.
     *
     * @param string $name The name attribute for the textarea, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $args Optional arguments: 'default', 'class', 'placeholder', 'description', 'rows', 'dependencies', 'sanitize' (bool, default true).
     * @return void
     */
    public function textarea(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? '');
        $class = $args['class'] ?? 'large-text';
        $rows  = $args['rows'] ?? 5;

        // Allow disabling sanitization for raw HTML/JS. Default is true.
        $should_sanitize = $args['sanitize'] ?? true;
        $display_value = $should_sanitize ? esc_html($value) : $value;

        $inputHtml = sprintf(
            '<textarea id="%1$s" name="%1$s" class="%2$s" rows="%3$s" placeholder="%4$s">%5$s</textarea>',
            esc_attr($name),
            esc_attr($class),
            absint($rows),
            esc_attr($args['placeholder'] ?? ''),
            $display_value // Use potentially unsanitized value
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a WordPress Editor (TinyMCE) field.
     *
     * @param string $name The name attribute for the editor, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $args Optional arguments: 'default', 'description', 'dependencies', and any standard wp_editor() settings.
     * @return void
     */
    public function wp_editor(string $name, string $label, array $args = []): void
    {
        $content = $this->option->get($name, $args['default'] ?? '');

        // Default settings for wp_editor, can be overridden by user args
        $editor_settings = array_merge(
            [
                'textarea_name' => $name,
                'textarea_rows' => $args['rows'] ?? 10,
                'media_buttons' => true,
                'tinymce'       => true,
            ],
            $args
        );

        // wp_editor() prints directly, so we use an output buffer to capture it.
        ob_start();
        wp_editor($content, $name, $editor_settings);
        $inputHtml = ob_get_clean();

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a modern switcher (toggle) field.
     *
     * @param string $name The name attribute for the checkbox, used as the option key.
     * @param string $label The main label for the field.
     * @param array  $args Optional arguments: 'default', 'description', 'dependencies'.
     * @return void
     */
    public function switcher(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? false);

        $inputHtml = sprintf(
            '<label class="field-switcher">
                <input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s class="field-switcher-checkbox">
                <span class="field-switcher-slider"></span>
            </label>',
            esc_attr($name),
            checked($value, '1', false)
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a single checkbox field.
     *
     * @param string $name The name attribute for the checkbox, used as the option key.
     * @param string $label The main label for the field.
     * @param array  $args Optional arguments: 'default', 'description', 'label_for_check', 'dependencies'.
     * @return void
     */
    public function checkbox(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? false);

        $inputHtml = sprintf(
            '<label for="%1$s">
                <input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s>
                %3$s
            </label>',
            esc_attr($name),
            checked($value, '1', false),
            esc_html($args['label_for_check'] ?? '')
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a select (dropdown) field.
     *
     * @param string $name The name attribute for the select, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $options An associative array of options (value => text).
     * @param array  $args Optional arguments: 'default', 'class', 'description', 'dependencies'.
     * @return void
     */
    public function select(string $name, string $label, array $options, array $args = []): void
    {
        $currentValue = $this->option->get($name, $args['default'] ?? '');
        $class = $args['class'] ?? '';

        $optionsHtml = '';
        foreach ($options as $value => $text) {
            $optionsHtml .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($currentValue, $value, false),
                esc_html($text)
            );
        }

        $inputHtml = sprintf(
            '<select id="%1$s" name="%1$s" class="%2$s">%3$s</select>',
            esc_attr($name),
            esc_attr($class),
            $optionsHtml
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a set of radio buttons.
     *
     * @param string $name The name attribute for the radio buttons, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $options An associative array of options (value => text).
     * @param array  $args Optional arguments: 'default', 'description', 'dependencies'.
     * @return void
     */
    public function radio(string $name, string $label, array $options, array $args = []): void
    {
        $currentValue = $this->option->get($name, $args['default'] ?? '');

        $radiosHtml = '<fieldset>';
        foreach ($options as $value => $text) {
            $radioId = esc_attr($name) . '-' . esc_attr($value);
            $radiosHtml .= sprintf(
                '<label for="%s" style="display: block; margin-bottom: 5px;">
                    <input type="radio" id="%s" name="%s" value="%s" %s> %s
                </label>',
                $radioId,
                $radioId,
                esc_attr($name),
                esc_attr($value),
                checked($currentValue, $value, false),
                esc_html($text)
            );
        }
        $radiosHtml .= '</fieldset>';

        $this->render($name, $label, $radiosHtml, $args);
    }

    /**
     * Renders a WordPress media uploader field.
     *
     * @param string $name The name attribute for the hidden input, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $args Optional arguments: 'default', 'description', 'button_text', 'dependencies'.
     * @return void
     */
    public function media(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? '');
        $buttonText = $args['button_text'] ?? 'Upload Image';
        $imageUrl = $value ? wp_get_attachment_image_url($value, 'medium') : '';

        $inputHtml = sprintf(
            '<div class="media-uploader-wrapper">
                <input type="hidden" id="%1$s" name="%1$s" value="%2$s" class="media-uploader-id">
                <div class="media-uploader-preview" style="%4$s">
                    <img src="%3$s" style="%4$s">
                </div>
                <button type="button" class="button media-uploader-button">%5$s</button>
                <button type="button" class="button media-remover-button" style="%4$s">Remove Image</button>
            </div>',
            esc_attr($name),
            esc_attr($value),
            esc_url($imageUrl),
            $imageUrl ? '' : 'display:none;',
            esc_html($buttonText)
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a WordPress color picker field.
     *
     * @param string $name The name attribute for the input, used as the option key.
     * @param string $label The label text for the field.
     * @param array  $args Optional arguments: 'default', 'description', 'dependencies'.
     * @return void
     */
    public function color(string $name, string $label, array $args = []): void
    {
        $value = $this->option->get($name, $args['default'] ?? '#000000');

        $inputHtml = sprintf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="wp-color-picker-field">',
            esc_attr($name),
            esc_attr($value)
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a repeater field for dynamically adding groups of fields.
     *
     * @param string $name  The option name for the repeater.
     * @param string $label The label for the repeater block.
     * @param array  $args  Configuration arguments. Must include a 'fields' array.
     *                      'fields' => [ [ 'type' => 'text', 'name' => 'sub_field_name', 'label' => 'Sub Field Label' ], ... ]
     * @return void
     */
    public function repeater(string $name, string $label, array $args = []): void
    {
        $this->enqueue_repeater_assets();

        $description = $args['description'] ?? '';
        $dependencies = $args['dependencies'] ?? [];
        $dependencyAttr = !empty($dependencies) ? sprintf('data-dependencies="%s"', esc_attr(json_encode($dependencies))) : '';

        // Get saved data, ensure it's an array
        $rows = $this->option->get($name, []);
        $rows = is_array($rows) ? $rows : [];

        // --- Start Repeater Wrapper ---
        echo sprintf(
            '<div class="field-wrapper field-repeater-wrapper" id="wrapper-%s" %s>',
            esc_attr($name),
            $dependencyAttr
        );

        // --- Repeater Header ---
        echo sprintf(
            '<div class="field-label"><label>%s</label></div>',
            esc_html($label)
        );
        if ($description) {
            echo sprintf('<p class="description">%s</p>', wp_kses_post($description));
        }

        // --- Repeater Content ---
        echo sprintf('<div class="field-repeater" data-repeater-name="%s">', esc_attr($name));
        echo '<div class="repeater-body">';

        // --- Render Existing Rows ---
        if (!empty($rows)) {
            foreach ($rows as $index => $row_data) {
                $this->render_repeater_row($name, $index, $args['fields'], $row_data);
            }
        }

        echo '</div>'; // .repeater-body

        // --- Repeater Footer with "Add Row" button ---
        echo '<div class="repeater-footer">';
        echo sprintf(
            '<button type="button" class="button repeater-add-row">%s</button>',
            esc_html($args['add_button_text'] ?? 'Add Row')
        );
        echo '</div>';

        echo '</div>'; // .field-repeater

        // --- Hidden Template Row for JavaScript ---
        echo '<script type="text/template" class="repeater-template">';
        $this->render_repeater_row($name, '__i__', $args['fields'], []);
        echo '</script>';

        echo '</div>'; // .field-wrapper
    }

    /**
     * Renders a single row for a repeater field.
     * Can be a saved row or a template row for JS cloning.
     *
     * @param string     $repeater_name The name of the main repeater field.
     * @param string|int $index         The index of the row (e.g., 0, 1, or '__i__' for the template).
     * @param array      $sub_fields    The configuration for the fields within the row.
     * @param array      $row_data      The data for the current row.
     */
    private function render_repeater_row(string $repeater_name, $index, array $sub_fields, array $row_data): void
    {
        echo '<div class="repeater-row">';
        echo '<div class="repeater-row-header"><span class="repeater-row-handle"></span><button type="button" class="repeater-remove-row">&times;</button></div>';
        echo '<div class="repeater-row-content">';

        foreach ($sub_fields as $field_args) {
            $field_type = $field_args['type'] ?? 'text';
            $field_name = $field_args['name'] ?? '';
            $field_label = $field_args['label'] ?? '';
            $value = $row_data[$field_name] ?? ($field_args['default'] ?? '');

            // Construct the input name: repeater_name[index][field_name]
            $input_name = sprintf('%s[%s][%s]', $repeater_name, $index, $field_name);
            $input_id = sprintf('%s_%s_%s', $repeater_name, $index, $field_name);

            echo '<div class="repeater-sub-field">';
            echo sprintf('<label for="%s">%s</label>', esc_attr($input_id), esc_html($field_label));

            // Generate the input based on type
            switch ($field_type) {
                case 'text':
                    echo sprintf('<input type="text" name="%s" id="%s" value="%s" class="widefat">', esc_attr($input_name), esc_attr($input_id), esc_attr($value));
                    break;
                case 'textarea':
                    echo sprintf('<textarea name="%s" id="%s" class="widefat" rows="3">%s</textarea>', esc_attr($input_name), esc_attr($input_id), esc_html($value));
                    break;
                case 'number':
                    echo sprintf('<input type="number" name="%s" id="%s" value="%s" class="small-text">', esc_attr($input_name), esc_attr($input_id), esc_attr($value));
                    break;
                // Add other simple field types here as needed (e.g., select, checkbox)
            }
            echo '</div>'; // .repeater-sub-field
        }

        echo '</div>'; // .repeater-row-content
        echo '</div>'; // .repeater-row
    }

    /**
     * Enqueues the necessary JS and CSS for the repeater field.
     * Ensures assets are only loaded once per page load.
     */
    private function enqueue_repeater_assets(): void
    {
        if (self::$repeater_assets_enqueued) {
            return;
        }

        // IMPORTANT: Adjust this URL to point to your actual JavaScript file.
        // This example assumes the file is in 'assets/js/repeater-field.js' relative to this PHP file's directory.
        $js_url = plugin_dir_url(__FILE__) . 'assets/js/repeater-field.js';

        wp_enqueue_script(
            'neonwebid-repeater-field',
            $js_url,
            ['jquery', 'jquery-ui-sortable'], // Repeater depends on jQuery and jQuery UI Sortable
            '1.0.0',
            true // Load in footer
        );

        self::$repeater_assets_enqueued = true;
    }

    /**
     * Renders the HTML wrapper for a standard (non-repeater) field.
     *
     * @param string $name      The 'for' attribute of the label.
     * @param string $label     The text for the <label>.
     * @param string $inputHtml The fully generated HTML for the input field(s).
     * @param array  $args      The arguments array for the field.
     */
    private function render(string $name, string $label, string $inputHtml, array $args): void
    {
        $description = $args['description'] ?? '';
        $dependencies = $args['dependencies'] ?? [];
        $dependencyAttr = !empty($dependencies) ? sprintf('data-dependencies="%s"', esc_attr(json_encode($dependencies))) : '';

        printf(
            '<div class="field-wrapper" id="wrapper-%s" %s>
                <div class="field-label">
                    <label for="%s">%s</label>
                </div>
                <div class="field-input">
                    %s
                    %s
                </div>
            </div>',
            esc_attr($name),
            $dependencyAttr,
            esc_attr($name),
            esc_html($label),
            $inputHtml, // Already escaped or buffered in the calling methods
            $description ? sprintf('<p class="description">%s</p>', wp_kses_post($description)) : ''
        );
    }
}