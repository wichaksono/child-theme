<?php
/**
 * System Info View
 *
 * This file is part of the Dev Tools plugin for WordPress.
 * It provides a view for displaying system information.
 *
 * @var WP_Theme $theme The current active theme object.
 * @var array $plugins An array of active plugin paths.
 *
 * @package DevTools
 * @subpackage Views
 */
defined('ABSPATH') || exit; ?>

<table class="widefat striped">
    <tbody>
    <tr>
        <th scope="row">WordPress Version</th>
        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
    </tr>
    <tr>
        <th scope="row">PHP Version</th>
        <td><?php echo esc_html(phpversion()); ?></td>
    </tr>
    <tr>
        <th scope="row">MySQL Version</th>
        <td><?php echo esc_html($GLOBALS['wpdb']->db_version()); ?></td>
    </tr>
    <tr>
        <th scope="row">Web Server</th>
        <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
    </tr>
    <tr>
        <th scope="row">PHP Memory Limit</th>
        <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
    </tr>
    <tr>
        <th scope="row">Max Upload Size</th>
        <td><?php echo esc_html(size_format(wp_max_upload_size())); ?></td>
    </tr>
    <tr>
        <th scope="row">Post Max Size</th>
        <td><?php echo esc_html(ini_get('post_max_size')); ?></td>
    </tr>
    <tr>
        <th scope="row">Max Execution Time</th>
        <td><?php echo esc_html(ini_get('max_execution_time')) . ' seconds'; ?></td>
    </tr>
    <tr>
        <th scope="row">WP_DEBUG</th>
        <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></td>
    </tr>
    <tr>
        <th scope="row">WP_ENV</th>
        <td><?php echo defined('WP_ENV') ? esc_html(WP_ENV) : 'Not defined'; ?></td>
    </tr>
    <tr>
        <th scope="row">Active Theme</th>
        <td><?php echo esc_html($theme->get('Name') . ' v' . $theme->get('Version')); ?></td>
    </tr>
    <tr>
        <th scope="row">Active Plugins</th>
        <td>
            <ul>
                <?php
                if (empty($plugins)) {
                    echo '<li>No active plugins</li>';
                }
                foreach ($plugins as $plugin_path) {
                    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path, false, false);
                    echo '<li>' . esc_html($plugin_data['Name'] . ' v' . $plugin_data['Version']) . '</li>';
                }
                ?>
            </ul>
        </td>
    </tr>
    <tr>
        <th scope="row">Site Language</th>
        <td><?php echo get_locale(); ?></td>
    </tr>
    <tr>
        <th scope="row">Site URL</th>
        <td><?php echo esc_url(home_url()); ?></td>
    </tr>
    <tr>
        <th scope="row">Theme Directory</th>
        <td><?php echo esc_html(get_stylesheet_directory()); ?></td>
    </tr>
    <tr>
        <th scope="row">Plugin Directory</th>
        <td><?php echo esc_html(WP_PLUGIN_DIR); ?></td>
    </tr>
    </tbody>
</table>
