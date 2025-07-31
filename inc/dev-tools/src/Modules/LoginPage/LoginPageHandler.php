<?php

namespace NeonWebId\DevTools\Modules\LoginPage;

use function add_action;
use function add_filter;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_bloginfo;
use function home_url;
use function wp_get_attachment_image_url;

/**
 * Class LoginPageHandler
 *
 * Applies all customizations to the WordPress login page.
 */
final class LoginPageHandler
{
    private array $config;

    public function __construct(array $config = [])
    {
        $defaults = [
            'login_logo_image_url' => '',
            'login_logo_link_url'  => home_url(),
            'login_logo_text'      => get_bloginfo('name'),
            'form_background_color'=> '#FFFFFF',
            'text_link_color'      => '#0073AA',
            'button_color'         => '#0073AA',
            'button_hover_color'   => '#005177',
            'login_footer_text'    => '',
            'login_footer_url'     => home_url(),
        ];

        $this->config = wp_parse_args($config, $defaults);

        $this->add_hooks();
    }

    private function add_hooks(): void
    {
        add_filter('login_headerurl', [$this, 'custom_login_logo_url']);
        add_filter('login_headertext', [$this, 'custom_login_logo_text']);
        add_action('login_enqueue_scripts', [$this, 'custom_login_styles']);
        add_action('login_footer', [$this, 'custom_login_footer_text']);
    }

    public function custom_login_logo_url(): string
    {
        return esc_url($this->config['login_logo_link_url']);
    }

    public function custom_login_logo_text(): string
    {
        return esc_html($this->config['login_logo_text']);
    }

    public function custom_login_footer_text(): void
    {
        $text = $this->config['login_footer_text'];
        $url = $this->config['login_footer_url'];

        if (!empty($text)) {
            echo '<p id="custom-login-footer-text">';
            if (!empty($url)) {
                echo '<a href="' . esc_url($url) . '" tabindex="-1">' . esc_html($text) . '</a>';
            } else {
                echo esc_html($text);
            }
            echo '</p>';
        }
    }

    public function custom_login_styles(): void
    {
        $logo_url = $this->config['login_logo_image_url'] ? wp_get_attachment_image_url($this->config['login_logo_image_url'], 'medium') : '';
        ?>
        <style type="text/css">
            body.login { background-color: #f0f2f5; }
            .login #login { width: 360px; }
            <?php if ($logo_url) : ?>
            #login h1 a, .login h1 a {
                background-image: url('<?php echo esc_url($logo_url); ?>');
                height: 80px;
                width: 100%;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center bottom;
            }
            <?php endif; ?>
            #loginform {
                background: <?php echo esc_attr($this->config['form_background_color']); ?>;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,.1);
            }
            .login form .input, .login input[type=text] {
                border-radius: 5px;
            }
            .wp-core-ui .button-primary {
                background: <?php echo esc_attr($this->config['button_color']); ?>;
                border-color: <?php echo esc_attr($this->config['button_color']); ?>;
                box-shadow: none;
                text-shadow: none;
                border-radius: 5px;
                transition: background-color 0.2s ease-in-out;
            }
            .wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus {
                background: <?php echo esc_attr($this->config['button_hover_color']); ?>;
                border-color: <?php echo esc_attr($this->config['button_hover_color']); ?>;
            }
            #nav a, #backtoblog a, #custom-login-footer-text a {
                color: <?php echo esc_attr($this->config['text_link_color']); ?>;
                transition: color 0.2s ease-in-out;
            }
            #nav a:hover, #backtoblog a:hover, #custom-login-footer-text a:hover {
                color: <?php echo esc_attr($this->config['button_hover_color']); ?>;
            }
            #custom-login-footer-text { text-align: center; }
        </style>
        <?php
    }
}