<?php

namespace NeonWebId\DevTools\Modules\HideWPLogin;

use function add_action;
use function add_filter;
use function add_query_arg;
use function get_option;
use function get_template_part;
use function home_url;
use function is_ssl;
use function is_user_logged_in;
use function remove_action;
use function status_header;
use function untrailingslashit;
use function wp_die;
use function wp_redirect;

/**
 * Class HideWPLoginHandler
 *
 * Handles all the logic for changing the WordPress login URL.
 */
final class HideWPLoginHandler
{
    private string $new_login_slug;
    private string $redirect_type;

    public function __construct(array $config = [])
    {
        $this->new_login_slug = $config['login_slug'] ?? 'login';
        $this->redirect_type  = $config['redirect_type'] ?? '404';

        $this->add_hooks();
    }

    private function add_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'plugins_loaded_hooks']);
        add_action('wp_loaded', [$this, 'wp_loaded_hooks']);
        add_filter('site_url', [$this, 'filter_login_url'], 10, 2);
        add_filter('network_site_url', [$this, 'filter_login_url'], 10, 2);
        add_filter('wp_redirect', [$this, 'filter_login_url'], 10, 2);
        add_filter('site_option_welcome_email', [$this, 'filter_login_url']);

        remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
    }

    public function plugins_loaded_hooks(): void
    {
        global $pagenow;
        $request_uri = $_SERVER['REQUEST_URI'];

        if ($pagenow === 'wp-login.php' || str_contains($request_uri, 'wp-login.php')) {
            // Check if the request is for the actual wp-login.php, not our new slug
            if (!str_contains($request_uri, $this->new_login_slug)) {
                $this->handle_unauthorized_access();
            }
        } elseif (is_admin() && !is_user_logged_in() && !defined('DOING_AJAX') && !str_contains($request_uri, 'admin-post.php')) {
            $this->handle_unauthorized_access();
        }
    }

    public function wp_loaded_hooks(): void
    {
        global $pagenow;
        $request_path = untrailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        if ($request_path === home_url($this->new_login_slug, 'relative')) {
            $pagenow = 'wp-login.php';
        }
    }

    private function handle_unauthorized_access(): void
    {
        switch ($this->redirect_type) {
            case 'home':
                wp_redirect(home_url());
                exit;
            case 'custom':
                wp_die('The login page is not available.', 'Login Page Not Found', ['response' => 404]);
                exit;
            default: // 404
                global $wp_query;
                if ($wp_query) {
                    $wp_query->set_404();
                    status_header(404);
                    get_template_part(404);
                }
                exit;
        }
    }

    public function filter_login_url($url): string
    {
        if (str_contains($url, 'wp-login.php')) {
            $query_string = parse_url($url, PHP_URL_QUERY);
            $new_url = home_url($this->new_login_slug);
            if ($query_string) {
                $new_url = add_query_arg($query_string, '', $new_url);
            }
            return $new_url;
        }
        return $url;
    }
}