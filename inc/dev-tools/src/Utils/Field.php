<?php

namespace NeonWebId\DevTools\Utils;

use function absint;
use function checked;
use function esc_attr;
use function esc_html;
use function esc_url;
use function json_encode;
use function printf;
use function selected;
use function sprintf;
use function wp_editor;
use function wp_get_attachment_image_url;
use function wp_kses_post;

/**
 * Class Field
 *
 * A factory class for generating a wide range of HTML form fields for the WordPress admin area.
 * It integrates with the DevOption class to automatically populate field values from nested option arrays,
 * supports name prefixing for organized data storage, and includes advanced fields
 * like repeaters, media uploaders, and dependency handling.
 *
 * @author wichaksono
 * @date   2025-07-30
 * @package NeonWebId\DevTools\Utils
 */
final class Field
{
    private DevOption $option;
    private static bool $repeater_assets_enqueued = false;
    private string $name_prefix;
    private string $id;

    /**
     * Field constructor.
     *
     * @param DevOption $option The dependency for getting and setting option values.
     * @param string $prefix An optional prefix for the 'name' attribute of all fields.
     * @param string $id An optional identifier to act as a group key for options.
     */
    public function __construct(DevOption $option, string $prefix = '', string $id = '')
    {
        $this->option      = $option;
        $this->name_prefix = $prefix;
        $this->id          = $id;

        if ($id) {
            $this->name_prefix = sprintf('%s[%s]', $this->name_prefix, $id);
        }
    }

    /**
     * Retrieves a specific value from the options group array.
     *
     * @param string $name The key of the value to retrieve.
     * @param mixed|null $default The default value to return if the key doesn't exist.
     *
     * @return mixed
     */
    private function getValue(string $name, mixed $default = null): mixed
    {
        // **PERBAIKAN KUNCI DI SINI**
        // Pertama, coba dapatkan nilai dari penyimpanan sementara repeater.
        $temp_value = $this->option->getTempRepeaterValue($name);
        if ($temp_value !== null) {
            return $temp_value;
        }

        // Jika tidak ada di sana (artinya ini bukan sub-field repeater),
        // lanjutkan dengan logika normal.
        if (empty($this->id)) {
            return $this->option->get($name, $default);
        }
        $values = $this->option->get($this->id);
        return $values[$name] ?? $default;
    }

    /**
     * Generates the correct 'name' attribute for a field.
     *
     * @param string $name The base name of the field.
     *
     * @return string The formatted name (e.g., '_dev_tools[utilities][disable_comments]').
     */
    public function get_prefixed_name(string $name): string
    {
        return sprintf('%s[%s]', $this->name_prefix, $name);
    }

    /**
     * Renders a text input field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., class, placeholder, default value).
     */
    public function text(string $name, string $label, array $args = []): void
    {
        $value           = $this->getValue($name, $args['default'] ?? '');
        $class           = $args['class'] ?? 'regular-text';
        $should_sanitize = $args['sanitize'] ?? true;
        $display_value   = $should_sanitize ? esc_attr($value) : $value;

        $inputHtml = sprintf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" placeholder="%4$s">',
            esc_attr($name),
            $display_value,
            esc_attr($class),
            esc_attr($args['placeholder'] ?? '')
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a number input field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., class, placeholder, default value, min, max, step).
     */
    public function number(string $name, string $label, array $args = []): void
    {
        $value     = $this->getValue($name, $args['default'] ?? '');
        $class     = $args['class'] ?? 'small-text';
        $min_attr  = isset($args['min']) ? sprintf('min="%s"', esc_attr($args['min'])) : '';
        $max_attr  = isset($args['max']) ? sprintf('max="%s"', esc_attr($args['max'])) : '';
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
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., class, placeholder, default value, rows).
     */
    public function textarea(string $name, string $label, array $args = []): void
    {
        $value           = $this->getValue($name, $args['default'] ?? '');
        $class           = $args['class'] ?? 'large-text';
        $rows            = $args['rows'] ?? 5;
        $should_sanitize = $args['sanitize'] ?? true;
        $display_value   = $should_sanitize ? esc_html($value) : $value;

        $inputHtml = sprintf(
            '<textarea id="%1$s" name="%1$s" class="%2$s" rows="%3$s" placeholder="%4$s">%5$s</textarea>',
            esc_attr($name),
            esc_attr($class),
            absint($rows),
            esc_attr($args['placeholder'] ?? ''),
            $display_value
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a WordPress editor field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., rows, default value, media buttons).
     */
    public function wp_editor(string $name, string $label, array $args = []): void
    {
        $content         = $this->getValue($name, $args['default'] ?? '');
        $editor_settings = array_merge([
            'textarea_name' => $name,
            'textarea_rows' => $args['rows'] ?? 10,
            'media_buttons' => true,
            'tinymce'       => true
        ], $args);
        ob_start();
        wp_editor($content, $name, $editor_settings);
        $inputHtml = ob_get_clean();

        $args['type'] = 'wp_editor';
        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a switcher field (checkbox styled as a switch).
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., default value).
     */
    public function switcher(string $name, string $label, array $args = []): void
    {
        $value     = $this->getValue($name, $args['default'] ?? false);
        $inputHtml = sprintf(
            '<label class="field-switcher"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s class="field-switcher-checkbox"><span class="field-switcher-slider"></span></label>',
            esc_attr($name),
            checked($value, '1', false)
        );
        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a checkbox field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., default value, label for check).
     */
    public function checkbox(string $name, string $label, array $args = []): void
    {
        $value     = $this->getValue($name, $args['default'] ?? false);
        $inputHtml = sprintf(
            '<label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr($name),
            checked($value, '1', false),
            esc_html($args['label_for_check'] ?? '')
        );
        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a select dropdown field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $options An associative array of options (value => text).
     * @param array $args Additional arguments for the field (e.g., default value, class).
     */
    public function select(string $name, string $label, array $options, array $args = []): void
    {
        $currentValue = $this->getValue($name, $args['default'] ?? '');
        $class        = $args['class'] ?? '';
        $optionsHtml  = '';
        foreach ($options as $value => $text) {
            $optionsHtml .= sprintf('<option value="%s" %s>%s</option>', esc_attr($value),
                selected($currentValue, $value, false), esc_html($text));
        }
        $inputHtml = sprintf('<select id="%1$s" name="%1$s" class="%2$s">%3$s</select>', esc_attr($name),
            esc_attr($class), $optionsHtml);
        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a radio button field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $options An associative array of options (value => text).
     * @param array $args Additional arguments for the field (e.g., default value).
     */
    public function radio(string $name, string $label, array $options, array $args = []): void
    {
        $currentValue = $this->getValue($name, $args['default'] ?? '');
        $radiosHtml   = '<fieldset>';
        foreach ($options as $value => $text) {
            $radioId    = esc_attr($name) . '-' . esc_attr($value);
            $radiosHtml .= sprintf(
                '<label for="%s" style="display: block; margin-bottom: 5px;"><input type="radio" id="%s" name="%s" value="%s" %s> %s</label>',
                $radioId, $radioId, esc_attr($name), esc_attr($value), checked($currentValue, $value, false),
                esc_html($text)
            );
        }
        $radiosHtml .= '</fieldset>';
        $this->render($name, $label, $radiosHtml, $args);
    }

    /**
     * Renders a media uploader field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., default value, button text).
     */
    public function media(string $name, string $label, array $args = []): void
    {
        $value      = $this->getValue($name, $args['default'] ?? '');
        $buttonText = $args['button_text'] ?? 'Upload Image';
        $imageUrl   = $value ? wp_get_attachment_image_url($value, 'medium') : '';
        $inputHtml  = sprintf(
            '<div class="media-uploader-wrapper"><input type="hidden" id="%1$s" name="%1$s" value="%2$s" class="media-uploader-id"><div class="media-uploader-preview" style="%4$s"><img src="%3$s" style="%4$s"></div><button type="button" class="button media-uploader-button">%5$s</button><button type="button" class="button media-remover-button" style="%4$s">Remove Image</button></div>',
            esc_attr($name), esc_attr($value), esc_url($imageUrl), $imageUrl ? '' : 'display:none;',
            esc_html($buttonText)
        );
        $this->render($name, $label, $inputHtml, $args);
    }

    /**
     * Renders a color picker field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., default value).
     */
    public function color(string $name, string $label, array $args = []): void
    {
        $value     = $this->getValue($name, $args['default'] ?? '#000000');
        $inputHtml = sprintf('<input type="text" id="%1$s" name="%1$s" value="%2$s" class="wp-color-picker-field">',
            esc_attr($name), esc_attr($value));
        $this->render($name, $label, $inputHtml, $args);
    }


    /**
     * Renders a repeater field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param array $args Additional arguments for the field (e.g., fields, add button text, description, dependencies).
     */
    /**
     * Renders a repeater field.
     */
    public function repeater(string $name, string $label, array $args = []): void
    {
        $rows          = (array)$this->getValue($name, []);
        $prefixed_name = $this->get_prefixed_name($name);

        echo sprintf('<div class="field-wrapper field-repeater-wrapper" id="wrapper-%s">', esc_attr($name));
        echo sprintf('<div class="field-label"><label>%s</label></div>', esc_html($label));
        if ( ! empty($args['description'])) {
            echo sprintf('<p class="description">%s</p>', wp_kses_post($args['description']));
        }
        echo sprintf('<div class="field-repeater" data-repeater-name="%s">', esc_attr($prefixed_name));
        echo '<div class="repeater-body">';
        if ( ! empty($rows)) {
            foreach ($rows as $index => $row_data) {
                $this->render_repeater_row($prefixed_name, $index, $args['fields'], $row_data);
            }
        }
        echo '</div>';
        echo '<div class="repeater-footer">';
        echo sprintf('<button type="button" class="button repeater-add-row">%s</button>',
            esc_html($args['add_button_text'] ?? 'Add Row'));
        echo '</div>';
        echo '</div>';
        echo '<script type="text/template" class="repeater-template">';
        $this->render_repeater_row($prefixed_name, '__i__', $args['fields'], []);
        echo '</script>';
        echo '</div>';
    }

    private function render_repeater_row(string $repeater_name, $index, array $sub_fields, array $row_data): void
    {
        // Tentukan apakah baris ini baru (tidak ada data) atau sudah ada.
        // Baris baru akan terbuka, baris lama akan tertutup secara default.
        $is_new_row = empty($row_data);
        $row_class = 'repeater-row' . ($is_new_row ? '' : ' is-collapsed');

        // Ambil nilai dari field pertama untuk dijadikan judul.
        $title_field_id = $sub_fields[0]['id'] ?? '';
        $row_title = !empty($title_field_id) && !empty($row_data[$title_field_id]) ? esc_html($row_data[$title_field_id]) : 'New Item';

        echo '<div class="' . esc_attr($row_class) . '">';

        // Header yang sudah ditingkatkan dengan judul dan tombol toggle
        echo '<div class="repeater-row-header">';
        echo '  <span class="repeater-row-handle"></span>';
        echo '  <span class="repeater-row-title">' . $row_title . '</span>';
        echo '  <span class="repeater-row-toggle"></span>'; // Tombol collapse/expand
        echo '  <button type="button" class="repeater-remove-row">&times;</button>';
        echo '</div>';

        // Konten yang bisa di-collapse
        echo '<div class="repeater-row-content">';

        foreach ($sub_fields as $field_args) {
            $field_type = $field_args['type'] ?? 'text';
            $field_id   = $field_args['id'] ?? '';
            $field_label = $field_args['label'] ?? '';

            $sub_field_prefix = sprintf('%s[%s]', $repeater_name, $index);
            $sub_field_instance = new Field($this->option, $sub_field_prefix);

            $current_value = $row_data[$field_id] ?? ($field_args['default'] ?? '');

            $sub_field_instance->option->setTempRepeaterValue($field_id, $current_value);
            $sub_field_instance->render_field($field_type, $field_id, $field_label, $field_args);
        }
        echo '</div></div>';
    }

    /**
     * Generic field renderer, now correctly private as it's an internal helper.
     * It's called by the public methods (text, number, etc.).
     */
    private function render_field(string $type, string $name, string $label, array $args): void
    {
        $value     = $this->getValue($name, $args['default'] ?? '');
        $inputHtml = $this->get_input_html($type, $name, $value, $args);
        $this->render_wrapper($name, $label, $inputHtml, $args);
    }

    private function get_input_html(string $type, string $name, mixed $value, array $args): string
    {
        switch ($type) {
            case 'text':
            case 'date':
            case 'number':
                $class = $args['class'] ?? ($type === 'number' ? 'small-text' : 'regular-text');
                return sprintf('<input type="%s" id="%s" name="%s" value="%s" class="%s" placeholder="%s">',
                    esc_attr($type), esc_attr($name), esc_attr($name), esc_attr($value), esc_attr($class),
                    esc_attr($args['placeholder'] ?? ''));

            case 'textarea':
                return sprintf('<textarea id="%1$s" name="%1$s" class="%2$s" rows="%3$s" placeholder="%4$s">%5$s</textarea>',
                    esc_attr($name), esc_attr($args['class'] ?? 'large-text'), absint($args['rows'] ?? 5),
                    esc_attr($args['placeholder'] ?? ''), esc_html($value));

            case 'switcher':
                return sprintf('<label class="field-switcher"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s class="field-switcher-checkbox"><span class="field-switcher-slider"></span></label>',
                    esc_attr($name), checked($value, '1', false));

            case 'checkbox':
                return sprintf('<label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>',
                    esc_attr($name), checked($value, '1', false), esc_html($args['label_for_check'] ?? ''));

            case 'select':
                $optionsHtml = '';
                foreach (($args['options'] ?? []) as $opt_val => $opt_text) {
                    $optionsHtml .= sprintf('<option value="%s" %s>%s</option>', esc_attr($opt_val),
                        selected($value, $opt_val, false), esc_html($opt_text));
                }
                return sprintf('<select id="%1$s" name="%1$s" class="%2$s">%3$s</select>', esc_attr($name),
                    esc_attr($args['class'] ?? ''), $optionsHtml);

            case 'color':
                return sprintf('<input type="text" id="%s" name="%s" value="%s" class="wp-color-picker-field">',
                    esc_attr($name), esc_attr($value), esc_attr($args['default'] ?? '#000000'));

            case 'image':
                $imageUrl = $value ? wp_get_attachment_image_url($value, 'medium') : '';
                $hasImage = ! empty($imageUrl);
                return sprintf('<div class="media-uploader-wrapper"><input type="hidden" id="%1$s" name="%1$s" value="%2$s" class="media-uploader-id"><div class="media-uploader-preview" style="%4$s"><img src="%3$s" style="%4$s"></div><button type="button" class="button media-uploader-button">%5$s</button><button type="button" class="button media-remover-button" style="%6$s">Remove Image</button></div>',
                    esc_attr($name), esc_attr($value), esc_url($imageUrl), $hasImage ? '' : 'display:none;',
                    esc_html($args['button_text'] ?? 'Upload'), $hasImage ? '' : 'display:none;');

            default:
                return '';
        }
    }

    private function render_wrapper(string $name, string $label, string $inputHtml, array $args): void
    {
        $prefixed_name = $this->get_prefixed_name($name);
        $final_html    = str_replace(sprintf('name="%s"', esc_attr($name)),
            sprintf('name="%s"', esc_attr($prefixed_name)), $inputHtml);
        $final_html    = str_replace(sprintf('id="%s"', esc_attr($name)), sprintf('id="%s"', esc_attr($prefixed_name)),
            $final_html);

        printf(
            '<div class="field-wrapper" id="wrapper-%s"><div class="field-label"><label for="%s">%s</label></div><div class="field-input">%s%s</div></div>',
            esc_attr($name),
            esc_attr($prefixed_name),
            esc_html($label),
            $final_html,
            ! empty($args['description']) ? sprintf('<p class="description">%s</p>',
                wp_kses_post($args['description'])) : ''
        );
    }

    /**
     * Renders the HTML structure for a field.
     *
     * @param string $name The name of the field.
     * @param string $label The label for the field.
     * @param string $inputHtml The HTML for the input element.
     * @param array $args Additional arguments for the field (e.g., description, dependencies).
     */
    private function render(string $name, string $label, string $inputHtml, array $args): void
    {
        $description    = $args['description'] ?? '';
        $dependencies   = $args['dependencies'] ?? [];
        $dependencyAttr = ! empty($dependencies) ? sprintf('data-dependencies="%s"',
            esc_attr(json_encode($dependencies))) : '';
        $prefixed_name  = $this->get_prefixed_name($name);

        $inputHtml = str_replace(sprintf('name="%s"', esc_attr($name)), sprintf('name="%s"', esc_attr($prefixed_name)),
            $inputHtml);

        if (($args['type'] ?? '') === 'wp_editor') {
            $inputHtml = str_replace(sprintf('id="%s"', esc_attr($name)), sprintf('id="%s"', esc_attr($prefixed_name)),
                $inputHtml);
        }

        printf(
            '<div class="field-wrapper" id="wrapper-%s" %s><div class="field-label"><label for="%s">%s</label></div><div class="field-input">%s%s</div></div>',
            esc_attr($name),
            $dependencyAttr,
            esc_attr($name),
            esc_html($label),
            $inputHtml,
            $description ? sprintf('<p class="description">%s</p>', wp_kses_post($description)) : ''
        );
    }
}