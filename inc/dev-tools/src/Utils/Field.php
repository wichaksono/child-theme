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
     * @param string    $prefix An optional prefix for the 'name' attribute of all fields.
     * @param string    $id     An optional identifier to act as a group key for options.
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
     * @param string $name    The key of the value to retrieve.
     * @param mixed|null $default The default value to return if the key doesn't exist.
     *
     * @return mixed
     */
    private function getValue(string $name, mixed $default = null): mixed
    {
        if (empty($this->id)) {
            // Fallback for non-grouped fields, though the pattern assumes grouping.
            return $this->option->get($name, $default);
        }
        $values = $this->option->get($this->id);
        return $values[$name] ?? $default;
    }

    /**
     * Generates the correct 'name' attribute for a field.
     *
     * @param string $name The base name of the field.
     * @return string The formatted name (e.g., '_dev_tools[utilities][disable_comments]').
     */
    private function get_prefixed_name(string $name): string
    {
        return sprintf('%s[%s]', $this->name_prefix, $name);
    }

    public function text(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? '');
        $class = $args['class'] ?? 'regular-text';
        $should_sanitize = $args['sanitize'] ?? true;
        $display_value = $should_sanitize ? esc_attr($value) : $value;

        $inputHtml = sprintf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" placeholder="%4$s">',
            esc_attr($name),
            $display_value,
            esc_attr($class),
            esc_attr($args['placeholder'] ?? '')
        );

        $this->render($name, $label, $inputHtml, $args);
    }

    public function number(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? '');
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

    public function textarea(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? '');
        $class = $args['class'] ?? 'large-text';
        $rows = $args['rows'] ?? 5;
        $should_sanitize = $args['sanitize'] ?? true;
        $display_value = $should_sanitize ? esc_html($value) : $value;

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

    public function wp_editor(string $name, string $label, array $args = []): void
    {
        $content = $this->getValue($name, $args['default'] ?? '');
        $editor_settings = array_merge(['textarea_name' => $name, 'textarea_rows' => $args['rows'] ?? 10, 'media_buttons' => true, 'tinymce' => true], $args);
        ob_start();
        wp_editor($content, $name, $editor_settings);
        $inputHtml = ob_get_clean();

        $args['type'] = 'wp_editor';
        $this->render($name, $label, $inputHtml, $args);
    }

    public function switcher(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? false);
        $inputHtml = sprintf(
            '<label class="field-switcher"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s class="field-switcher-checkbox"><span class="field-switcher-slider"></span></label>',
            esc_attr($name),
            checked($value, '1', false)
        );
        $this->render($name, $label, $inputHtml, $args);
    }

    public function checkbox(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? false);
        $inputHtml = sprintf(
            '<label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr($name),
            checked($value, '1', false),
            esc_html($args['label_for_check'] ?? '')
        );
        $this->render($name, $label, $inputHtml, $args);
    }

    public function select(string $name, string $label, array $options, array $args = []): void
    {
        $currentValue = $this->getValue($name, $args['default'] ?? '');
        $class = $args['class'] ?? '';
        $optionsHtml = '';
        foreach ($options as $value => $text) {
            $optionsHtml .= sprintf('<option value="%s" %s>%s</option>', esc_attr($value), selected($currentValue, $value, false), esc_html($text));
        }
        $inputHtml = sprintf('<select id="%1$s" name="%1$s" class="%2$s">%3$s</select>', esc_attr($name), esc_attr($class), $optionsHtml);
        $this->render($name, $label, $inputHtml, $args);
    }

    public function radio(string $name, string $label, array $options, array $args = []): void
    {
        $currentValue = $this->getValue($name, $args['default'] ?? '');
        $radiosHtml = '<fieldset>';
        foreach ($options as $value => $text) {
            $radioId = esc_attr($name) . '-' . esc_attr($value);
            $radiosHtml .= sprintf(
                '<label for="%s" style="display: block; margin-bottom: 5px;"><input type="radio" id="%s" name="%s" value="%s" %s> %s</label>',
                $radioId, $radioId, esc_attr($name), esc_attr($value), checked($currentValue, $value, false), esc_html($text)
            );
        }
        $radiosHtml .= '</fieldset>';
        $this->render($name, $label, $radiosHtml, $args);
    }

    public function media(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? '');
        $buttonText = $args['button_text'] ?? 'Upload Image';
        $imageUrl = $value ? wp_get_attachment_image_url($value, 'medium') : '';
        $inputHtml = sprintf(
            '<div class="media-uploader-wrapper"><input type="hidden" id="%1$s" name="%1$s" value="%2$s" class="media-uploader-id"><div class="media-uploader-preview" style="%4$s"><img src="%3$s" style="%4$s"></div><button type="button" class="button media-uploader-button">%5$s</button><button type="button" class="button media-remover-button" style="%4$s">Remove Image</button></div>',
            esc_attr($name), esc_attr($value), esc_url($imageUrl), $imageUrl ? '' : 'display:none;', esc_html($buttonText)
        );
        $this->render($name, $label, $inputHtml, $args);
    }

    public function color(string $name, string $label, array $args = []): void
    {
        $value = $this->getValue($name, $args['default'] ?? '#000000');
        $inputHtml = sprintf('<input type="text" id="%1$s" name="%1$s" value="%2$s" class="wp-color-picker-field">', esc_attr($name), esc_attr($value));
        $this->render($name, $label, $inputHtml, $args);
    }

    public function repeater(string $name, string $label, array $args = []): void
    {
        $this->enqueue_repeater_assets();
        $description = $args['description'] ?? '';
        $dependencies = $args['dependencies'] ?? [];
        $dependencyAttr = !empty($dependencies) ? sprintf('data-dependencies="%s"', esc_attr(json_encode($dependencies))) : '';
        $rows = $this->getValue($name, []);
        $rows = is_array($rows) ? $rows : [];
        $prefixed_name = $this->get_prefixed_name($name);

        echo sprintf('<div class="field-wrapper field-repeater-wrapper" id="wrapper-%s" %s>', esc_attr($name), $dependencyAttr);
        echo sprintf('<div class="field-label"><label>%s</label></div>', esc_html($label));
        if ($description) {
            echo sprintf('<p class="description">%s</p>', wp_kses_post($description));
        }
        echo sprintf('<div class="field-repeater" data-repeater-name="%s">', esc_attr($prefixed_name));
        echo '<div class="repeater-body">';
        if (!empty($rows)) {
            foreach ($rows as $index => $row_data) {
                $this->render_repeater_row($prefixed_name, $index, $args['fields'], $row_data);
            }
        }
        echo '</div>';
        echo '<div class="repeater-footer">';
        echo sprintf('<button type="button" class="button repeater-add-row">%s</button>', esc_html($args['add_button_text'] ?? 'Add Row'));
        echo '</div>';
        echo '</div>';
        echo '<script type="text/template" class="repeater-template">';
        $this->render_repeater_row($prefixed_name, '__i__', $args['fields'], []);
        echo '</script>';
        echo '</div>';
    }

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
            $input_name = sprintf('%s[%s][%s]', $repeater_name, $index, $field_name);
            $input_id = sprintf('%s_%s_%s', str_replace(['[', ']'], '_', $repeater_name), $index, $field_name);

            echo '<div class="repeater-sub-field">';
            echo sprintf('<label for="%s">%s</label>', esc_attr($input_id), esc_html($field_label));
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
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    private function enqueue_repeater_assets(): void
    {
        if (self::$repeater_assets_enqueued) {
            return;
        }
        $js_url = plugin_dir_url(__FILE__) . 'assets/js/repeater-field.js';
        wp_enqueue_script('neonwebid-repeater-field', $js_url, ['jquery', 'jquery-ui-sortable'], '1.0.0', true);
        self::$repeater_assets_enqueued = true;
    }

    private function render(string $name, string $label, string $inputHtml, array $args): void
    {
        $description = $args['description'] ?? '';
        $dependencies = $args['dependencies'] ?? [];
        $dependencyAttr = !empty($dependencies) ? sprintf('data-dependencies="%s"', esc_attr(json_encode($dependencies))) : '';
        $prefixed_name = $this->get_prefixed_name($name);

        $inputHtml = str_replace(sprintf('name="%s"', esc_attr($name)), sprintf('name="%s"', esc_attr($prefixed_name)), $inputHtml);

        if (($args['type'] ?? '') === 'wp_editor') {
            $inputHtml = str_replace(sprintf('id="%s"', esc_attr($name)), sprintf('id="%s"', esc_attr($prefixed_name)), $inputHtml);
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