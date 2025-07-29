<?php

namespace NeonWebId\DevTools\Utils\ThemeUpdater;

use Exception;
use WP_Theme;
use WP_Upgrader;

/**
 * Theme Updater Class
 *
 * Handles automatic theme updates from a private server
 *
 * @package NeonWebId\DevTools\Utils\ThemeUpdater
 * @author wichaksono
 * @version 1.0.0
 */
final class ThemeUpdater
{
    /**
     * Theme slug/directory name
     *
     * @var string
     */
    private string $theme_slug = '';

    /**
     * Theme version
     *
     * @var string
     */
    private string $version = '';

    /**
     * Update server URL
     *
     * @var string
     */
    private string $update_server = '';

    /**
     * Theme data
     *
     * @var WP_Theme|null
     */
    private ?WP_Theme $theme_data = null;

    /**
     * Set theme slug
     *
     * @param string $theme_slug Theme directory name
     *
     * @return self
     */
    public function setThemeSlug(string $theme_slug): self
    {
        $this->theme_slug = $theme_slug;
        $this->theme_data = wp_get_theme($this->theme_slug);
        return $this;
    }

    /**
     * Set theme version
     *
     * @param string $version Current theme version
     *
     * @return self
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Set update server URL
     *
     * @param string $update_server Update server URL
     *
     * @return self
     */
    public function setUpdateServer(string $update_server): self
    {
        $this->update_server = trailingslashit($update_server);
        return $this;
    }

    /**
     * Initialize the updater
     *
     * @return void
     */
    public function init(): void
    {
        if (empty($this->theme_slug) || empty($this->version) || empty($this->update_server)) {
            return;
        }

        add_filter('pre_set_site_transient_update_themes', [$this, 'check_for_update']);
        add_filter('themes_api', [$this, 'theme_info'], 10, 3);
        add_action('upgrader_process_complete', [$this, 'clear_cache'], 10, 2);
        add_action('wp_ajax_theme_update_check', [$this, 'ajax_check_update']);
    }

    /**
     * Check for theme updates
     *
     * @param mixed $transient
     *
     * @return mixed
     */
    public function check_for_update(mixed $transient): mixed
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get remote version info
        $remote_version = $this->get_remote_version();

        if ($remote_version && version_compare($this->version, $remote_version['version'], '<')) {
            $transient->response[$this->theme_slug] = [
                'theme'       => $this->theme_slug,
                'new_version' => $remote_version['version'],
                'url'         => $remote_version['details_url'] ?? '',
                'package'     => $this->get_download_url($remote_version)
            ];
        }

        return $transient;
    }

    /**
     * Get remote version information
     *
     * @return array|false
     */
    private function get_remote_version(): false|array
    {
        $cache_key   = 'theme_update_' . $this->theme_slug;
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }

        $request_args = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
                'Accept'     => 'application/json',
            ],
            'body'    => [
                'action'          => 'get_version',
                'theme_slug'      => $this->theme_slug,
                'current_version' => $this->version,
                'site_url'        => home_url(),
                'wp_version'      => get_bloginfo('version'),
                'php_version'     => PHP_VERSION,
            ]
        ];

        $response = wp_remote_post($this->update_server . 'api/theme-update/', $request_args);

        if (is_wp_error($response)) {
            error_log('Theme Update Error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ( ! $data || ! isset($data['version'])) {
            return false;
        }

        // Cache for 12 hours
        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);

        return $data;
    }

    /**
     * Get download URL for the update
     *
     * @param array $version_data
     *
     * @return string
     */
    private function get_download_url(array $version_data): string
    {
        $download_args = [
            'theme_slug'     => $this->theme_slug,
            'site_url'       => home_url(),
            'download_token' => $version_data['download_token'] ?? '',
        ];

        return add_query_arg($download_args, $this->update_server . 'api/theme-download/');
    }

    /**
     * Handle theme information requests
     *
     * @param mixed $result
     * @param string $action
     * @param object $args
     *
     * @return mixed
     */
    public function theme_info(mixed $result, string $action, object $args): mixed
    {
        if ($action !== 'theme_information' || $args->slug !== $this->theme_slug) {
            return $result;
        }

        $remote_version = $this->get_remote_version();

        if ( ! $remote_version) {
            return $result;
        }

        return (object)[
            'name'            => $this->theme_data->get('Name'),
            'slug'            => $this->theme_slug,
            'version'         => $remote_version['version'],
            'author'          => $this->theme_data->get('Author'),
            'author_profile'  => $this->theme_data->get('AuthorURI'),
            'contributors'    => [],
            'requires'        => $remote_version['requires_wp'] ?? '5.0',
            'tested'          => $remote_version['tested_wp'] ?? get_bloginfo('version'),
            'requires_php'    => $remote_version['requires_php'] ?? '7.4',
            'last_updated'    => $remote_version['last_updated'] ?? date('Y-m-d H:i:s'),
            'homepage'        => $this->theme_data->get('ThemeURI'),
            'sections'        => [
                'description'  => $remote_version['description'] ?? $this->theme_data->get('Description'),
                'changelog'    => $remote_version['changelog'] ?? 'No changelog available.',
                'installation' => $remote_version['installation'] ?? 'Standard WordPress theme installation.',
                'faq'          => $remote_version['faq'] ?? '',
            ],
            'download_link'   => $this->get_download_url($remote_version),
            'banners'         => $remote_version['banners'] ?? [],
            'icons'           => $remote_version['icons'] ?? [],
            'screenshots'     => $remote_version['screenshots'] ?? [],
            'rating'          => 100,
            'num_ratings'     => 1,
            'downloaded'      => 0,
            'active_installs' => 1,
        ];
    }

    /**
     * Clear update cache after upgrade
     *
     * @param WP_Upgrader $upgrader
     * @param array $hook_extra
     *
     * @return void
     */
    public function clear_cache(WP_Upgrader $upgrader, array $hook_extra): void
    {
        if (
            isset($hook_extra['type']) && $hook_extra['type'] === 'theme' &&
            isset($hook_extra['themes']) && in_array($this->theme_slug, $hook_extra['themes'])
        ) {
            delete_transient('theme_update_' . $this->theme_slug);

            // Clear WordPress update cache
            delete_site_transient('update_themes');
        }
    }

    /**
     * AJAX handler for manual update check
     *
     * @return void
     */
    public function ajax_check_update(): void
    {
        check_ajax_referer('theme_update_nonce', 'nonce');

        if ( ! current_user_can('update_themes')) {
            wp_die('Insufficient permissions');
        }

        // Clear cache and force check
        delete_transient('theme_update_' . $this->theme_slug);

        $remote_version = $this->get_remote_version();

        if ($remote_version && version_compare($this->version, $remote_version['version'], '<')) {
            wp_send_json_success([
                'message'          => 'Update available',
                'current_version'  => $this->version,
                'new_version'      => $remote_version['version'],
                'update_available' => true
            ]);
        } else {
            wp_send_json_success([
                'message'          => 'Theme is up to date',
                'current_version'  => $this->version,
                'update_available' => false
            ]);
        }
    }

    /**
     * Force check for updates
     *
     * @return array|false
     */
    public function forceCheck(): array|false
    {
        delete_transient('theme_update_' . $this->theme_slug);
        return $this->get_remote_version();
    }

    /**
     * Get update server status
     *
     * @return bool
     */
    public function serverStatus(): bool
    {
        $response = wp_remote_get($this->update_server . 'api/status/', ['timeout' => 10]);

        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_response_code($response) === 200;
    }


    /**
     * Get theme data
     *
     * @return WP_Theme|null
     */
    public function getThemeData(): ?WP_Theme
    {
        return $this->theme_data;
    }

    /**
     * Reset configuration
     *
     * @return void
     */
    public function reset(): void
    {
        $this->theme_slug    = '';
        $this->version       = '';
        $this->update_server = '';
        $this->theme_data    = null;
    }
}