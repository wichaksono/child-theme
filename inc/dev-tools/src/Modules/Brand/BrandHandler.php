<?php

namespace NeonWebId\DevTools\Modules\Brand;

use WP_Admin_Bar;
use function add_action;
use function add_filter;
use function add_menu_page;
use function admin_url;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_bloginfo;
use function get_current_screen;
use function home_url;
use function remove_menu_page;
use function wp_parse_args;
use function wp_redirect;

/**
 * Class BrandHandler
 *
 * Applies all branding modifications to the WordPress admin area.
 */
final class BrandHandler
{
    private string $adminLogoImageUrl;
    private string $adminBrandText;
    private string $adminBrandLinkUrl;
    private string $adminFooterText;
    private bool $removeWpVersion;
    private string $customDashboardSlug;

    public function __construct(array $config = [])
    {
        $defaults = [
            'admin_logo_image_url' => '',
            'admin_brand_text'     => get_bloginfo('name'),
            'admin_brand_link_url' => home_url(),
            'admin_footer_text'    => '',
            'remove_wp_version'    => false,
            'custom_dashboard_slug' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        $this->adminLogoImageUrl = $config['admin_logo_image_url'];
        $this->adminBrandText    = $config['admin_brand_text'];
        $this->adminBrandLinkUrl = $config['admin_brand_link_url'];
        $this->adminFooterText   = $config['admin_footer_text'];
        $this->removeWpVersion   = (bool) $config['remove_wp_version'];
        $this->customDashboardSlug = $config['custom_dashboard_slug'];

        $this->add_hooks();
    }

    private function add_hooks(): void
    {
        add_action('admin_bar_menu', [$this, 'add_custom_admin_bar_logo'], 1);
        add_action('admin_bar_menu', [$this, 'remove_default_wp_logo_node'], 99);
        add_action('admin_enqueue_scripts', [$this, 'custom_admin_styles']);
        add_filter('admin_footer_text', [$this, 'custom_admin_footer_text']);

        if ($this->removeWpVersion) {
            add_filter('update_footer', [$this, 'remove_wp_version_from_footer'], 11);
        }

        if (!empty($this->customDashboardSlug)) {
            add_action('admin_menu', [$this, 'add_custom_dashboard_page']);
            add_action('load-index.php', [$this, 'redirect_dashboard']);
        }
    }

    public function add_custom_admin_bar_logo(WP_Admin_Bar $wp_admin_bar): void
    {
        $args = [
            'id'    => 'custom-wp-logo',
            'title' => '<img src="' . esc_url($this->adminLogoImageUrl) . '" alt="' . esc_attr($this->adminBrandText) . '" class="custom-admin-bar-logo" />' . esc_html($this->adminBrandText),
            'href'  => esc_url($this->adminBrandLinkUrl),
            'meta'  => ['title' => esc_attr($this->adminBrandText)],
        ];
        if (empty($this->adminLogoImageUrl)) {
            $args['title'] = esc_html($this->adminBrandText);
        }
        $wp_admin_bar->add_node($args);
    }

    public function remove_default_wp_logo_node(WP_Admin_Bar $wp_admin_bar): void
    {
        $wp_admin_bar->remove_node('wp-logo');
    }

    public function custom_admin_styles(): void
    {
        echo '<style type="text/css">
            #wpadminbar #wp-admin-bar-custom-wp-logo > .ab-item { padding: 0; height: 32px; display: flex; align-items: center; }
            #wpadminbar #wp-admin-bar-custom-wp-logo .custom-admin-bar-logo { height: 20px; width: auto; margin-right: 8px; vertical-align: middle; }
            ' . (empty($this->adminLogoImageUrl) ? '' : '#wpadminbar #wp-admin-bar-custom-wp-logo .ab-icon:before { content: none !important; }') . '
        </style>';
    }

    public function custom_admin_footer_text(string $footer_text): string
    {
        return !empty($this->adminFooterText) ? esc_html($this->adminFooterText) : $footer_text;
    }

    public function remove_wp_version_from_footer(): string
    {
        return '';
    }

    public function add_custom_dashboard_page(): void
    {
        add_menu_page('Dashboard', 'Dashboard', 'read', $this->customDashboardSlug, [$this, 'custom_dashboard_page_content'], 'dashicons-admin-home', 1);
        remove_menu_page('index.php');
    }

    public function custom_dashboard_page_content(): void
    {
        // Anda bisa menaruh konten dashboard kustom di sini atau me-render sebuah view
        echo '<div class="wrap"><h1>Welcome to Your Custom Dashboard!</h1><p>This is where your custom content would go.</p></div>';
    }

    public function redirect_dashboard(): void
    {
        if (get_current_screen()->base === 'dashboard') {
            wp_redirect(admin_url('admin.php?page=' . $this->customDashboardSlug));
            exit;
        }
    }
}