<?php

namespace NeonWebId\DevTools\Modules\Utilities;

use WP_Error;

/**
 * Class UtilitiesHandler
 *
 * Applies various WordPress modifications (actions and filters) based on a
 * given set of options. Each utility, such as disabling comments or removing
 * meta tags, is handled by a dedicated method that is conditionally executed.
 *
 * @author   wichaksono
 * @date     2025-07-30
 * @package  NeonWebId\DevTools\Modules\Utilities
 */
final class UtilitiesHandler
{
    /**
     * The array of saved options for the utilities module.
     * e.g., ['disable_comments' => '1', 'remove_wp_version' => '1']
     *
     * @var array
     */
    private array $options;

    /**
     * UtilitiesHandler constructor.
     *
     * @param array $options The options array retrieved from the database.
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Initializes the handler, checking each feature and registering
     * the necessary WordPress hooks if the feature is enabled.
     *
     * @return void
     */
    public function init(): void
    {
        // Core Features
        if ($this->isEnabled('disable_comments')) {
            $this->handleDisableComments();
        }
        if ($this->isEnabled('disable_updates')) {
            $this->handleDisableUpdates();
        }
        if ($this->isEnabled('disable_wp_cron')) {
            $this->handleDisableWpCron();
        }
        if ($this->isEnabled('disable_file_editor')) {
            $this->handleDisableFileEditor();
        }

        // Security & API
        if ($this->isEnabled('disable_xmlrpc')) {
            $this->handleDisableXmlrpc();
        }
        if ($this->isEnabled('disable_rest_api')) {
            $this->handleDisableRestApi();
        }

        // Header & Meta Cleanup
        if ($this->isEnabled('disable_emoji')) {
            $this->handleDisableEmoji();
        }
        if ($this->isEnabled('remove_pingback')) {
            remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        }
        if ($this->isEnabled('remove_rsd_link')) {
            remove_action('wp_head', 'rsd_link');
        }
        if ($this->isEnabled('remove_wlwmanifest')) {
            remove_action('wp_head', 'wlwmanifest_link');
        }
        if ($this->isEnabled('remove_wp_version')) {
            remove_action('wp_head', 'wp_generator');
        }
        if ($this->isEnabled('remove_wp_generator')) {
            remove_action('wp_head', 'wp_generator');
        }
        if ($this->isEnabled('remove_shortlink')) {
            remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        }

        // Content Optimization
        if ($this->isEnabled('post_revisions')) {
            $this->handlePostRevisions();
        }
    }

    /**
     * Checks if a specific feature is enabled in the options.
     *
     * @param string $key The option key to check.
     *
     * @return bool True if the option exists and is considered "on".
     */
    private function isEnabled(string $key): bool
    {
        return ! empty($this->options[$key]);
    }

    /**
     * Disables comments site-wide.
     */
    private function handleDisableComments(): void
    {
        add_action('admin_init', function () {
            global $pagenow;
            if ($pagenow === 'edit-comments.php') {
                wp_redirect(admin_url());
                exit;
            }
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
            foreach (get_post_types() as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        });
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', '__return_empty_array', 10, 2);
        add_action('admin_menu', function () {
            remove_menu_page('edit-comments.php');
        });
        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('comments');
        });
    }

    /**
     * Disables all core, plugin, and theme updates.
     */
    private function handleDisableUpdates(): void
    {
        add_filter('pre_site_transient_update_core', [$this, 'removeCoreUpdates']);
        add_filter('pre_site_transient_update_plugins', [$this, 'removeCoreUpdates']);
        add_filter('pre_site_transient_update_themes', [$this, 'removeCoreUpdates']);
    }

    public function removeCoreUpdates(): object
    {
        global $wp_version;
        return (object)array(
            'last_checked'    => time(),
            'version_checked' => $wp_version,
            'updates'         => array()
        );
    }

    /**
     * Disables the built-in WP-Cron.
     */
    private function handleDisableWpCron(): void
    {
        if ( ! defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }
    }

    /**
     * Disables the Theme and Plugin file editors in the admin area.
     */
    private function handleDisableFileEditor(): void
    {
        if ( ! defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }

    /**
     * Disables the XML-RPC protocol.
     */
    private function handleDisableXmlrpc(): void
    {
        add_filter('xmlrpc_enabled', '__return_false');
    }

    /**
     * Disables the REST API for non-logged-in users.
     */
    private function handleDisableRestApi(): void
    {
        add_filter('rest_authentication_errors', function ($result) {
            if ( ! empty($result)) {
                return $result;
            }
            if ( ! is_user_logged_in()) {
                return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', ['status' => 401]);
            }
            return $result;
        });
    }

    /**
     * Removes emoji-related scripts and styles.
     */
    private function handleDisableEmoji(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }

    /**
     * Limits the number of post revisions based on the saved option.
     */
    private function handlePostRevisions(): void
    {
        $revision_count = $this->options['post_revisions_count'] ?? 3;
        $revision_count = absint($revision_count);

        if ( ! defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', $revision_count);
        }
    }
}