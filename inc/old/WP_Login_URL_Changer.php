<?php
/**
 * WordPress Login URL Changer Class
 *
 * Class untuk mengubah URL login WordPress default dari wp-admin dan wp-login.php
 * ke URL custom untuk meningkatkan keamanan
 *
 * @author wichaksono
 * @date 2025-07-29
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WP_Login_URL_Changer {

    private $option_name = 'wp_login_url_settings';
    private $new_login_slug = '';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('plugins_loaded', array($this, 'plugins_loaded'), 9999);
        add_action('wp_loaded', array($this, 'wp_loaded'));
        add_filter('site_url', array($this, 'site_url'), 10, 4);
        add_filter('network_site_url', array($this, 'network_site_url'), 10, 3);
        add_filter('wp_redirect', array($this, 'wp_redirect'), 10, 2);
        add_filter('site_option_welcome_email', array($this, 'welcome_email'));

        remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);

        $this->new_login_slug = $this->get_new_login_slug();
    }

    /**
     * Menambahkan menu ke wp-admin
     */
    public function add_admin_menu() {
        add_options_page(
            'Change Login URL',
            'Login URL',
            'manage_options',
            'wp-login-url',
            array($this, 'admin_page')
        );
    }

    /**
     * Inisialisasi pengaturan
     */
    public function init_settings() {
        register_setting('wp_login_url_group', $this->option_name, array($this, 'sanitize_settings'));

        add_settings_section(
            'wp_login_url_section',
            'Pengaturan URL Login',
            array($this, 'section_callback'),
            'wp-login-url'
        );

        add_settings_field(
            'login_slug',
            'Custom Login URL',
            array($this, 'login_slug_callback'),
            'wp-login-url',
            'wp_login_url_section'
        );

        add_settings_field(
            'redirect_page',
            'Redirect Page untuk URL Lama',
            array($this, 'redirect_page_callback'),
            'wp-login-url',
            'wp_login_url_section'
        );

        add_settings_field(
            'enable_feature',
            'Aktifkan Fitur',
            array($this, 'enable_feature_callback'),
            'wp-login-url',
            'wp_login_url_section'
        );
    }

    /**
     * Sanitize pengaturan
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['login_slug'])) {
            $sanitized['login_slug'] = sanitize_title($input['login_slug']);

            // Validasi slug tidak boleh sama dengan reserved words
            $reserved = array('admin', 'login', 'wp-admin', 'wp-login', 'dashboard', 'wp-login.php');
            if (in_array($sanitized['login_slug'], $reserved) || empty($sanitized['login_slug'])) {
                add_settings_error('wp_login_url_settings', 'invalid_slug', 'Login slug tidak boleh kosong atau menggunakan kata yang sudah direserved.');
                $sanitized['login_slug'] = get_option($this->option_name)['login_slug'] ?? 'secure-login';
            }
        }

        if (isset($input['redirect_page'])) {
            $sanitized['redirect_page'] = sanitize_text_field($input['redirect_page']);
        }

        $sanitized['enable_feature'] = isset($input['enable_feature']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * Callback untuk section
     */
    public function section_callback() {
        $current_url = home_url($this->new_login_slug);
        echo '<p>Ubah URL login WordPress untuk meningkatkan keamanan dari brute force attack.</p>';
        if ($this->is_feature_enabled()) {
            echo '<div class="notice notice-info"><p><strong>URL Login Saat Ini:</strong> <a href="' . esc_url($current_url) . '" target="_blank">' . esc_url($current_url) . '</a></p></div>';
        }
    }

    /**
     * Callback untuk login slug field
     */
    public function login_slug_callback() {
        $options = get_option($this->option_name);
        $value = isset($options['login_slug']) ? $options['login_slug'] : 'secure-login';

        echo '<input type="text" id="login_slug" name="' . $this->option_name . '[login_slug]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Masukkan slug custom untuk URL login (contoh: secure-login, my-admin, dll)</p>';
        echo '<p class="description"><strong>URL Login akan menjadi:</strong> ' . home_url() . '/<span id="preview-slug">' . esc_html($value) . '</span></p>';
    }

    /**
     * Callback untuk redirect page field
     */
    public function redirect_page_callback() {
        $options = get_option($this->option_name);
        $value = isset($options['redirect_page']) ? $options['redirect_page'] : '404';

        echo '<select id="redirect_page" name="' . $this->option_name . '[redirect_page]">';
        echo '<option value="404"' . selected($value, '404', false) . '>404 Page</option>';
        echo '<option value="home"' . selected($value, 'home', false) . '>Home Page</option>';
        echo '<option value="custom"' . selected($value, 'custom', false) . '>Custom Message</option>';
        echo '</select>';
        echo '<p class="description">Pilih halaman tujuan ketika ada yang mengakses wp-admin atau wp-login.php</p>';
    }

    /**
     * Callback untuk enable feature field
     */
    public function enable_feature_callback() {
        $options = get_option($this->option_name);
        $checked = isset($options['enable_feature']) ? checked($options['enable_feature'], 1, false) : '';

        echo '<input type="checkbox" id="enable_feature" name="' . $this->option_name . '[enable_feature]" value="1" ' . $checked . ' />';
        echo '<label for="enable_feature">Aktifkan perubahan URL login</label>';
        echo '<p class="description"><strong>Peringatan:</strong> Pastikan Anda mengingat URL login baru sebelum mengaktifkan fitur ini!</p>';
    }

    /**
     * Halaman admin
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Change Login URL</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_login_url_group');
                do_settings_sections('wp-login-url');
                submit_button('Simpan Pengaturan');
                ?>
            </form>

            <div class="card">
                <h2>Informasi Penting</h2>
                <ul>
                    <li><strong>Backup:</strong> Selalu backup website sebelum mengaktifkan fitur ini</li>
                    <li><strong>Bookmark:</strong> Simpan URL login baru di bookmark browser</li>
                    <li><strong>Akses:</strong> Jika lupa URL login, nonaktifkan plugin melalui FTP/cPanel</li>
                    <li><strong>Keamanan:</strong> Fitur ini akan menyembunyikan wp-admin dan wp-login.php dari umum</li>
                </ul>
            </div>
        </div>

        <script>
            document.getElementById('login_slug').addEventListener('input', function() {
                var slug = this.value || 'secure-login';
                document.getElementById('preview-slug').textContent = slug;
            });
        </script>

        <style>
            .wrap h1 {
                margin-bottom: 20px;
            }
            .form-table th {
                width: 200px;
            }
            .card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-top: 20px;
                max-width: 600px;
            }
            .card h2 {
                margin-top: 0;
                color: #d63638;
            }
            .card ul {
                margin-left: 20px;
            }
            .card li {
                margin-bottom: 8px;
            }
            .notice-info {
                padding: 10px;
                border-left: 4px solid #72aee6;
                background: #f0f6fc;
                margin: 15px 0;
            }
        </style>
        <?php
    }

    /**
     * Get custom login slug
     */
    private function get_new_login_slug() {
        $options = get_option($this->option_name);
        return isset($options['login_slug']) ? $options['login_slug'] : 'secure-login';
    }

    /**
     * Check if feature is enabled
     */
    private function is_feature_enabled() {
        $options = get_option($this->option_name);
        return isset($options['enable_feature']) && $options['enable_feature'] == 1;
    }

    /**
     * Plugin loaded hook
     */
    public function plugins_loaded() {
        if (!$this->is_feature_enabled()) {
            return;
        }

        global $pagenow;

        if (!is_multisite() &&
            (strpos($_SERVER['REQUEST_URI'], 'wp-signup') !== false ||
                strpos($_SERVER['REQUEST_URI'], 'wp-activate') !== false)) {
            wp_die(__('This feature is not enabled.'));
        }

        $request = parse_url($_SERVER['REQUEST_URI']);

        if ((strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false ||
                $pagenow === 'wp-login.php') &&
            $request['path'] !== $this->user_trailingslashit('/' . $this->new_login_slug)) {
            $this->handle_unauthorized_access();
        } elseif ((strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false &&
                strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') === false &&
                strpos($_SERVER['REQUEST_URI'], 'admin-post.php') === false) &&
            !is_user_logged_in()) {
            $this->handle_unauthorized_access();
        }
    }

    /**
     * WP loaded hook
     */
    public function wp_loaded() {
        if (!$this->is_feature_enabled()) {
            return;
        }

        global $pagenow;

        $request = parse_url($_SERVER['REQUEST_URI']);

        if (untrailingslashit($request['path']) === untrailingslashit(str_repeat('/', substr_count(trim($request['path'], '/'), '/') + 1) . $this->new_login_slug)) {
            $pagenow = 'wp-login.php';
            $_SERVER['SCRIPT_NAME'] = $this->user_trailingslashit($this->new_login_slug);
            require_once ABSPATH . 'wp-login.php';
            die();
        }
    }

    /**
     * Handle unauthorized access
     */
    private function handle_unauthorized_access() {
        $options = get_option($this->option_name);
        $redirect_type = isset($options['redirect_page']) ? $options['redirect_page'] : '404';

        switch ($redirect_type) {
            case 'home':
                wp_redirect(home_url());
                exit();
                break;
            case 'custom':
                wp_die('Page not found.', 'Error 404', array('response' => 404));
                break;
            default:
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                get_template_part(404);
                exit();
        }
    }

    /**
     * Filter site URL
     */
    public function site_url($url, $path, $scheme, $blog_id) {
        return $this->filter_wp_login_php($url, $scheme);
    }

    /**
     * Filter network site URL
     */
    public function network_site_url($url, $path, $scheme) {
        return $this->filter_wp_login_php($url, $scheme);
    }

    /**
     * Filter wp-login.php URLs
     */
    private function filter_wp_login_php($url, $scheme = null) {
        if (!$this->is_feature_enabled()) {
            return $url;
        }

        if (strpos($url, 'wp-login.php') !== false) {
            if (is_ssl()) {
                $scheme = 'https';
            }

            $args = explode('?', $url);

            if (isset($args[1])) {
                parse_str($args[1], $args);
                $url = add_query_arg($args, $this->new_login_url($scheme));
            } else {
                $url = $this->new_login_url($scheme);
            }
        }

        return $url;
    }

    /**
     * Get new login URL
     */
    private function new_login_url($scheme = null) {
        if (get_option('permalink_structure')) {
            return $this->user_trailingslashit(home_url('/', $scheme) . $this->new_login_slug);
        } else {
            return home_url('/', $scheme) . '?' . $this->new_login_slug;
        }
    }

    /**
     * Handle trailing slash
     */
    private function user_trailingslashit($string) {
        return untrailingslashit($string) . '/';
    }

    /**
     * Filter wp_redirect
     */
    public function wp_redirect($location, $status) {
        if (!$this->is_feature_enabled()) {
            return $location;
        }

        return $this->filter_wp_login_php($location);
    }

    /**
     * Filter welcome email
     */
    public function welcome_email($value) {
        return $this->filter_wp_login_php($value);
    }
}

// Inisialisasi class
new WP_Login_URL_Changer();
?>