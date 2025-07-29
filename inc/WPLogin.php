<?php
/**
 * Class for customizing the WordPress login page.
 */
class WpLoginCustomizer {

    /**
     * @var string The background color for the login form.
     */
    private string $loginFormBackground;

    /**
     * @var string The color for general text and links on the login page.
     */
    private string $textLabelColor;

    /**
     * @var string The background and border color for the primary login button.
     */
    private string $buttonColor;

    /**
     * @var string The background and border color for the primary login button on hover/focus.
     */
    private string $buttonHoverColor;

    /**
     * @var string The URL that the login logo links to.
     */
    private string $loginLogoLinkUrl;

    /**
     * @var string The URL of the custom image to be used as the login logo.
     */
    private string $loginLogoImageUrl;

    /**
     * @var string The alternative text for the login logo (important for accessibility).
     */
    private string $loginLogoText;

    /**
     * @var string The custom text to display in the login page footer.
     */
    private string $loginFooterText;

    /**
     * @var string The URL for the custom text in the login page footer.
     */
    private string $loginFooterUrl;

    /**
     * Class constructor.
     * Initializes WordPress hooks and sets configuration options.
     *
     * @param array<string, string> $config An associative array of configuration options.
     * Keys include: 'login_form_background', 'text_label_color',
     * 'button_color', 'button_hover_color', 'login_logo_link_url',
     * 'login_logo_image_url', 'login_logo_text',
     * 'login_footer_text', 'login_footer_url'.
     */
    public function __construct(array $config = []) {
        $defaults = [
            'login_form_background' => '#ffffff',
            'text_label_color'      => '#0073aa', // Default link color for #nav, #backtoblog
            'button_color'          => '#0073aa',
            'button_hover_color'    => '#005177',
            'login_logo_link_url'   => home_url(), // Where the logo links to
            'login_logo_image_url'  => get_stylesheet_directory_uri() . '/images/custom-login-logo.png',
            'login_logo_text'       => get_bloginfo('name'),
            'login_footer_text'     => 'Back to ' . get_bloginfo('name'),
            'login_footer_url'      => home_url(),
        ];

        // Merge user-provided config with defaults
        $config = wp_parse_args($config, $defaults);

        // Assign configuration values to class properties
        $this->loginFormBackground  = $config['login_form_background'];
        $this->textLabelColor       = $config['text_label_color'];
        $this->buttonColor          = $config['button_color'];
        $this->buttonHoverColor     = $config['button_hover_color'];
        $this->loginLogoLinkUrl     = $config['login_logo_link_url'];
        $this->loginLogoImageUrl    = $config['login_logo_image_url'];
        $this->loginLogoText        = $config['login_logo_text'];
        $this->loginFooterText      = $config['login_footer_text'];
        $this->loginFooterUrl       = $config['login_footer_url'];

        // Add WordPress hooks
        add_filter('login_headerurl', array($this, 'custom_login_logo_url'));
        add_filter('login_headertext', array($this, 'custom_login_logo_text'));
        add_action('login_enqueue_scripts', array($this, 'custom_login_styles'));
        add_action('login_footer', array($this, 'custom_login_footer_text')); // New hook for footer text
    }

    /**
     * Changes the URL of the login page logo link.
     *
     * @return string The new URL for the login logo.
     */
    public function custom_login_logo_url(): string {
        return $this->loginLogoLinkUrl;
    }

    /**
     * Changes the alternative text (alt text) or link text of the logo on the login page.
     * This is important for accessibility.
     *
     * @return string The new text for the login logo.
     */
    public function custom_login_logo_text(): string {
        return $this->loginLogoText;
    }

    /**
     * Adds custom styles to the login page.
     * This includes changing the logo image and adjusting other styles based on configuration.
     */
    public function custom_login_styles(): void {
        ?>
        <style type="text/css">
            /* Replace the WordPress logo image with your custom logo */
            #login h1 a {
                background-image: url('<?php echo esc_url($this->loginLogoImageUrl); ?>'); /* Configurable logo image URL */
                height: 100px; /* Adjust to your logo's height */
                width: 320px;  /* Adjust to your logo's width */
                background-size: contain; /* Ensures the logo fits within the area */
                background-repeat: no-repeat;
                padding-bottom: 30px; /* Provides some space below the logo */
            }

            /* Style for the login form */
            #loginform {
                background: <?php echo esc_attr($this->loginFormBackground); ?>; /* Configurable form background color */
                border-radius: 10px; /* Rounded corners */
                box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1); /* Soft shadow */
                padding: 30px;
            }

            /* Style for input fields */
            .login form .input {
                border: 1px solid #ddd;
                box-shadow: none;
                padding: 10px 12px;
                border-radius: 5px;
                margin-bottom: 15px;
            }

            /* Style for the login button */
            .wp-core-ui .button-primary {
                background: <?php echo esc_attr($this->buttonColor); ?>; /* Configurable button background color */
                border-color: <?php echo esc_attr($this->buttonColor); ?>;
                box-shadow: none;
                text-shadow: none;
                border-radius: 5px;
                padding: 8px 15px;
                height: auto;
                font-size: 16px;
                transition: background-color 0.3s ease; /* Transition effect on hover */
            }

            .wp-core-ui .button-primary:hover,
            .wp-core-ui .button-primary:focus {
                background: <?php echo esc_attr($this->buttonHoverColor); ?>; /* Configurable button hover color */
                border-color: <?php echo esc_attr($this->buttonHoverColor); ?>;
            }

            /* Style for text on the login page (e.g., "Lost your password?", "Back to blog") */
            #nav, #backtoblog {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
            }

            #nav a, #backtoblog a {
                color: <?php echo esc_attr($this->textLabelColor); ?>; /* Configurable text/link color */
                text-decoration: none;
                transition: color 0.3s ease;
            }

            #nav a:hover, #backtoblog a:hover {
                color: <?php echo esc_attr($this->buttonHoverColor); ?>; /* Use button hover color for consistency */
                text-decoration: underline;
            }

            /* Style for the custom footer text */
            #custom-login-footer-text {
                margin-top: 15px;
                font-size: 13px;
                text-align: center;
            }

            #custom-login-footer-text a {
                color: <?php echo esc_attr($this->textLabelColor); ?>;
                text-decoration: none;
            }

            #custom-login-footer-text a:hover {
                color: <?php echo esc_attr($this->buttonHoverColor); ?>;
                text-decoration: underline;
            }

            /* Adjust the overall width of the login page */
            body.login {
                background-color: #f0f2f5; /* Login page background color */
            }
            .login #login {
                width: 360px; /* Total width of the login area */
                padding: 8% 0 0; /* Top and bottom padding */
            }
        </style>
        <?php
    }

    /**
     * Adds custom text and link to the login page footer.
     */
    public function custom_login_footer_text(): void {
        if (!empty($this->loginFooterText)) {
            echo '<p id="custom-login-footer-text">';
            if (!empty($this->loginFooterUrl)) {
                echo '<a href="' . esc_url($this->loginFooterUrl) . '">' . esc_html($this->loginFooterText) . '</a>';
            } else {
                echo esc_html($this->loginFooterText);
            }
            echo '</p>';
        }
    }
}

// Example of how to initialize the class with custom configurations:
// To use default settings:
// new WpLoginCustomizer();

// To use custom settings:
new WpLoginCustomizer([
    'login_form_background' => '#e0f2f7', // Light blue background for form
    'text_label_color'      => '#1a73e8', // Google Blue for links
    'button_color'          => '#1a73e8', // Google Blue for button
    'button_hover_color'    => '#0d47a1', // Darker Google Blue on hover
    'login_logo_link_url'   => 'https://example.com/my-company/', // Custom URL for logo link
    'login_logo_image_url'  => get_stylesheet_directory_uri() . '/images/your-custom-logo.png', // Path to your custom logo image
    'login_logo_text'       => 'My Awesome Site Login', // Custom alt text for logo
    'login_footer_text'     => 'Visit Our Main Site', // Custom footer text
    'login_footer_url'      => 'https://example.com/', // Custom URL for footer text
]);

