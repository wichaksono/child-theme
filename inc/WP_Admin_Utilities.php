<?php
/**
 * WordPress Admin Utilities Class
 *
 * Class untuk membuat menu utilitas di wp-admin dengan berbagai opsi
 * untuk mengoptimalkan dan mengamankan WordPress
 *
 * @author wichaksono
 * @date 2025-07-29
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WP_Admin_Utilities {

    private $option_name = 'wp_admin_utilities_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('init', array($this, 'apply_settings'));
    }

    /**
     * Menambahkan menu ke wp-admin
     */
    public function add_admin_menu() {
        add_options_page(
            'Utilitas WordPress',
            'Utilitas',
            'manage_options',
            'wp-utilitas',
            array($this, 'admin_page')
        );
    }

    /**
     * Inisialisasi pengaturan
     */
    public function init_settings() {
        register_setting('wp_utilities_group', $this->option_name);

        add_settings_section(
            'wp_utilities_section',
            'Pengaturan Utilitas WordPress',
            array($this, 'section_callback'),
            'wp-utilitas'
        );

        $fields = array(
            'disable_comments' => 'Disable Comment',
            'disable_updates' => 'Disable Update',
            'disable_wp_cron' => 'Disable WP Cron',
            'disable_file_editor' => 'Nonaktifkan File Editor',
            'disable_xmlrpc' => 'Disable XML-RPC',
            'disable_rest_api' => 'Removes WordPress REST API',
            'disable_emoji' => 'Disable Emoji',
            'remove_pingback' => 'Removes Pingback Tag',
            'remove_rsd_link' => 'Removes rsd_link Meta',
            'remove_wlwmanifest' => 'Removes wlwmanifest Meta',
            'remove_wp_version' => 'Removes WordPress Version',
            'remove_wp_generator' => 'Removes WordPress Generator',
            'remove_shortlink' => 'Removes WordPress Shortlink'
        );

        foreach ($fields as $field_id => $field_title) {
            add_settings_field(
                $field_id,
                $field_title,
                array($this, 'field_callback'),
                'wp-utilitas',
                'wp_utilities_section',
                array('field_id' => $field_id)
            );
        }
    }

    /**
     * Callback untuk section
     */
    public function section_callback() {
        echo '<p>Pilih utilitas yang ingin Anda aktifkan untuk mengoptimalkan dan mengamankan WordPress.</p>';
    }

    /**
     * Callback untuk field checkbox
     */
    public function field_callback($args) {
        $options = get_option($this->option_name);
        $field_id = $args['field_id'];
        $checked = isset($options[$field_id]) ? checked($options[$field_id], 1, false) : '';

        echo "<input type='checkbox' id='{$field_id}' name='{$this->option_name}[{$field_id}]' value='1' {$checked} />";
    }

    /**
     * Halaman admin
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Utilitas WordPress</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_utilities_group');
                do_settings_sections('wp-utilitas');
                submit_button('Simpan Pengaturan');
                ?>
            </form>
        </div>
        <style>
            .wrap h1 {
                margin-bottom: 20px;
            }
            .form-table th {
                width: 300px;
            }
            .form-table input[type="checkbox"] {
                margin-right: 10px;
            }
        </style>
        <?php
    }

    /**
     * Menerapkan pengaturan yang dipilih
     */
    public function apply_settings() {
        $options = get_option($this->option_name);

        if (!$options) return;

        // Disable Comments - Lebih ketat untuk mencegah spam
        if (isset($options['disable_comments']) && $options['disable_comments']) {
            // Hapus dukungan comment dari semua post types
            add_action('admin_init', array($this, 'disable_comments_post_types_support'));

            // Tutup semua comment dan pingback
            add_filter('comments_open', '__return_false', 20, 2);
            add_filter('pings_open', '__return_false', 20, 2);

            // Kosongkan array comments
            add_filter('comments_array', '__return_empty_array', 10, 2);

            // Hapus menu comments dari admin
            add_action('admin_menu', array($this, 'remove_comments_menu'));
            add_action('admin_bar_menu', array($this, 'remove_comments_admin_bar'), 999);

            // Hapus support comment dari post types
            add_action('init', array($this, 'remove_comment_support'), 100);

            // Redirect wp-comments-post.php untuk mencegah spam
            add_action('init', array($this, 'disable_comment_form_submission'));

            // Hapus comment form dari tema
            add_filter('comments_template', array($this, 'disable_comments_template'), 20);

            // Hapus widget comments
            add_action('widgets_init', array($this, 'disable_comments_widget'));

            // Hapus metabox comments dari post editor
            add_action('admin_init', array($this, 'remove_comments_metabox'));

            // Hapus quick edit comment status
            add_action('admin_init', array($this, 'remove_comment_quick_edit'));

            // Redirect direct access ke wp-comments-post.php
            add_action('template_redirect', array($this, 'redirect_comments_post'));
        }

        // Disable Updates
        if (isset($options['disable_updates']) && $options['disable_updates']) {
            add_filter('pre_site_transient_update_core', '__return_null');
            add_filter('pre_site_transient_update_plugins', '__return_null');
            add_filter('pre_site_transient_update_themes', '__return_null');
        }

        // Disable WP Cron
        if (isset($options['disable_wp_cron']) && $options['disable_wp_cron']) {
            if (!defined('DISABLE_WP_CRON')) {
                define('DISABLE_WP_CRON', true);
            }
        }

        // Disable File Editor
        if (isset($options['disable_file_editor']) && $options['disable_file_editor']) {
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', true);
            }
        }

        // Disable XML-RPC
        if (isset($options['disable_xmlrpc']) && $options['disable_xmlrpc']) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', array($this, 'remove_x_pingback'));
            add_filter('xmlrpc_methods', array($this, 'remove_xmlrpc_methods'));
        }

        // Disable REST API
        if (isset($options['disable_rest_api']) && $options['disable_rest_api']) {
            add_filter('rest_enabled', '__return_false');
            add_filter('rest_jsonp_enabled', '__return_false');
            remove_action('wp_head', 'rest_output_link_wp_head', 10);
            remove_action('template_redirect', 'rest_output_link_header', 11);
        }

        // Disable Emoji
        if (isset($options['disable_emoji']) && $options['disable_emoji']) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
            add_filter('tiny_mce_plugins', array($this, 'disable_emojis_tinymce'));
            add_filter('wp_resource_hints', array($this, 'disable_emojis_dns_prefetch'), 10, 2);
        }

        // Remove Pingback
        if (isset($options['remove_pingback']) && $options['remove_pingback']) {
            remove_action('wp_head', 'rsd_link');
        }

        // Remove RSD Link
        if (isset($options['remove_rsd_link']) && $options['remove_rsd_link']) {
            remove_action('wp_head', 'rsd_link');
        }

        // Remove wlwmanifest
        if (isset($options['remove_wlwmanifest']) && $options['remove_wlwmanifest']) {
            remove_action('wp_head', 'wlwmanifest_link');
        }

        // Remove WordPress Version
        if (isset($options['remove_wp_version']) && $options['remove_wp_version']) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        // Remove WordPress Generator
        if (isset($options['remove_wp_generator']) && $options['remove_wp_generator']) {
            remove_action('wp_head', 'wp_generator');
        }

        // Remove Shortlink
        if (isset($options['remove_shortlink']) && $options['remove_shortlink']) {
            remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        }
    }

    /**
     * Helper functions untuk disable comments secara ketat
     */
    public function disable_comments_post_types_support() {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    public function remove_comments_menu() {
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    public function remove_comments_admin_bar($wp_admin_bar) {
        $wp_admin_bar->remove_node('comments');
    }

    public function remove_comment_support() {
        remove_post_type_support('post', 'comments');
        remove_post_type_support('page', 'comments');
    }

    public function disable_comment_form_submission() {
        if (is_admin()) return;

        if (isset($_POST['comment_post_ID']) || isset($_GET['replytocom'])) {
            wp_die('Comments are disabled.', 'Comments Disabled', array('response' => 403));
        }
    }

    public function disable_comments_template($template) {
        return dirname(__FILE__) . '/blank-comments.php';
    }

    public function disable_comments_widget() {
        unregister_widget('WP_Widget_Recent_Comments');
        add_filter('show_recent_comments_widget_style', '__return_false');
    }

    public function remove_comments_metabox() {
        remove_meta_box('commentstatusdiv', 'post', 'normal');
        remove_meta_box('commentstatusdiv', 'page', 'normal');
        remove_meta_box('commentsdiv', 'post', 'normal');
        remove_meta_box('trackbacksdiv', 'post', 'normal');
    }

    public function remove_comment_quick_edit() {
        add_filter('post_row_actions', array($this, 'remove_comment_quick_edit_actions'), 10, 1);
        add_filter('page_row_actions', array($this, 'remove_comment_quick_edit_actions'), 10, 1);
    }

    public function remove_comment_quick_edit_actions($actions) {
        unset($actions['inline hide-if-no-js']);
        return $actions;
    }

    public function redirect_comments_post() {
        global $pagenow;
        if ($pagenow === 'wp-comments-post.php') {
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * Helper functions untuk XML-RPC
     */
    public function remove_x_pingback($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function remove_xmlrpc_methods($methods) {
        return array();
    }

    /**
     * Helper functions untuk Emoji
     */
    public function disable_emojis_tinymce($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, array('wpemoji'));
        } else {
            return array();
        }
    }

    public function disable_emojis_dns_prefetch($urls, $relation_type) {
        if ('dns-prefetch' == $relation_type) {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
            $urls = array_diff($urls, array($emoji_svg_url));
        }
        return $urls;
    }
}

// Buat file blank comments template jika tidak ada
if (!file_exists(dirname(__FILE__) . '/blank-comments.php')) {
    file_put_contents(dirname(__FILE__) . '/blank-comments.php', '<?php // Comments are disabled ?>');
}

// Inisialisasi class
new WP_Admin_Utilities();
?>