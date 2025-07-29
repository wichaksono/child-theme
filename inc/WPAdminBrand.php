<?php
/**
 * Class for customizing the WordPress admin bar brand and dashboard.
 */
class WpAdminBrandCustomizer {

    /**
     * @var string The URL of the custom image to be used as the admin bar logo.
     */
    private string $adminLogoImageUrl;

    /**
     * @var string The text to display next to the logo in the admin bar.
     */
    private string $adminBrandText;

    /**
     * @var string The URL that the admin bar logo and text link to.
     */
    private string $adminBrandLinkUrl;

    /**
     * @var string The custom text to display in the admin footer (left side).
     */
    private string $adminFooterText;

    /**
     * @var bool Whether to remove the WordPress version text from the admin footer (right side).
     */
    private bool $removeWpVersion;

    /**
     * @var string The slug for the custom dashboard page.
     */
    private string $customDashboardSlug;

    /**
     * Class constructor.
     * Initializes WordPress hooks and sets configuration options for admin branding.
     *
     * @param array<string, mixed> $config An associative array of configuration options.
     * Keys include: 'admin_logo_image_url', 'admin_brand_text', 'admin_brand_link_url',
     * 'admin_footer_text', 'remove_wp_version', 'custom_dashboard_slug'.
     */
    public function __construct(array $config = []) {
        $defaults = [
            'admin_logo_image_url' => '', // Default to empty, meaning no custom image unless provided
            'admin_brand_text'     => get_bloginfo('name'), // Default to site name
            'admin_brand_link_url' => home_url(), // Default to site homepage
            'admin_footer_text'    => '', // Default to empty, meaning original WordPress "Thank you" text unless provided
            'remove_wp_version'    => false, // Default to keeping the WordPress version text
            'custom_dashboard_slug' => '', // Default to empty, meaning no custom dashboard
        ];

        // Merge user-provided config with defaults
        $config = wp_parse_args($config, $defaults);

        // Assign configuration values to class properties
        $this->adminLogoImageUrl = $config['admin_logo_image_url'];
        $this->adminBrandText    = $config['admin_brand_text'];
        $this->adminBrandLinkUrl = $config['admin_brand_link_url'];
        $this->adminFooterText   = $config['admin_footer_text'];
        $this->removeWpVersion   = (bool) $config['remove_wp_version']; // Cast to boolean
        $this->customDashboardSlug = $config['custom_dashboard_slug'];

        // Add WordPress hooks
        // Add the custom logo node early
        add_action('admin_bar_menu', array($this, 'add_custom_admin_bar_logo'), 1);
        // Remove the default WordPress logo node slightly later to ensure it's added first
        add_action('admin_bar_menu', array($this, 'remove_default_wp_logo_node'), 99); // New hook for removal

        add_action('admin_enqueue_scripts', array($this, 'custom_admin_styles'));
        add_filter('admin_footer_text', array($this, 'custom_admin_footer_text'));

        // Hook to remove/modify the WordPress version text in the footer
        if ($this->removeWpVersion) {
            add_filter('update_footer', array($this, 'remove_wp_version_from_footer'), 11); // Priority 11 to run after default
        }

        // Add hooks for custom dashboard
        if (!empty($this->customDashboardSlug)) {
            add_action('admin_menu', array($this, 'add_custom_dashboard_page'));
            add_action('load-index.php', array($this, 'redirect_dashboard')); // Redirect from default dashboard
        }
    }

    /**
     * Adds the custom logo node to the admin bar.
     * This runs with a low priority (1) to ensure it appears at the beginning.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
     * @return void
     */
    public function add_custom_admin_bar_logo(WP_Admin_Bar $wp_admin_bar): void {
        // Add a new custom logo node
        $args = array(
            'id'    => 'custom-wp-logo',
            'parent' => false,
            'group' => false,
            'title' => '<img src="' . esc_url($this->adminLogoImageUrl) . '" alt="' . esc_attr($this->adminBrandText) . '" class="custom-admin-bar-logo" />' . esc_html($this->adminBrandText),
            'href'  => esc_url($this->adminBrandLinkUrl),
            'meta'  => array(
                'title' => esc_attr($this->adminBrandText),
            ),
            'priority' => 1, // This priority ensures it's the first item within its group
        );

        // If no custom image URL is provided, just use text
        if (empty($this->adminLogoImageUrl)) {
            $args['title'] = esc_html($this->adminBrandText);
        }

        $wp_admin_bar->add_node($args);
    }

    /**
     * Removes the default WordPress logo node from the admin bar.
     * This runs with a higher priority (99) to ensure the default node has been added.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
     * @return void
     */
    public function remove_default_wp_logo_node(WP_Admin_Bar $wp_admin_bar): void {
        // Remove the default WordPress logo node
        $wp_admin_bar->remove_node('wp-logo');
    }

    /**
     * Adds custom styles to the admin area to adjust the logo appearance, hide Gutenberg logo,
     * and style the custom dashboard.
     *
     * @return void
     */
    public function custom_admin_styles(): void {
        ?>
        <style type="text/css">
            /* Admin Bar Styling */
            #wpadminbar #wp-admin-bar-custom-wp-logo > .ab-item {
                padding-top: 0;
                padding-bottom: 0;
                height: 32px; /* Standard admin bar height */
                display: flex;
                align-items: center;
                box-shadow: none !important; /* Remove any box-shadow */
            }

            #wpadminbar #wp-admin-bar-custom-wp-logo .custom-admin-bar-logo {
                height: 20px; /* Adjust logo height as needed */
                width: auto;
                margin-right: 8px; /* Space between logo and text */
                vertical-align: middle;
            }

            /* Hide the default WordPress icon in the main admin bar if a custom logo is used */
            <?php if (!empty($this->adminLogoImageUrl)) : ?>
            #wpadminbar #wp-admin-bar-custom-wp-logo .ab-icon:before {
                content: none !important;
            }
            <?php endif; ?>

            /* Adjust text color for better visibility if needed */
            #wpadminbar #wp-admin-bar-custom-wp-logo .ab-item,
            #wpadminbar #wp-admin-bar-custom-wp-logo .ab-item:hover {
                color: #fff !important; /* Ensure text is visible on dark admin bar */
            }

            /* Hide the WordPress logo in the Gutenberg editor header's back button and replace with custom SVG */
            <?php if (!empty($this->adminLogoImageUrl)) : ?>
            .edit-post-fullscreen-mode-close svg {
                display: none !important; /* Hide the original SVG */
            }

            .edit-post-fullscreen-mode-close.components-button {
                background:transparent !important; /* Ensure the button background is transparent */
            }

            .edit-post-fullscreen-mode-close {
                /* Ensure the button has relative positioning for the pseudo-element */
                position: relative;
                /* Ensure it's visible, overriding any previous display: none */
                display: flex !important;
                justify-content: center;
                align-items: center;
                /* Remove any background image that might be there from default WP styling if not desired */
                background-image: none !important;
            }

            .edit-post-fullscreen-mode-close::before {
                content: '';
                display: block;
                width: 100%; /* Fill the button's width */
                height: 100%; /* Fill the button's height */
                /* SVG for a left-pointing arrow */
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-arrow-left' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: center;
                background-size: 24px 24px; /* Size of the actual SVG icon within the button, adjust as needed */
                position: absolute;
                top: 0 !important;
                left: 0 !important;
                box-shadow: none !important; /* Remove any box-shadow */
            }

            /* The previous selector for the main menu toggle might still be useful for other contexts */
            .edit-post-header__toolbar .wp-block-editor-menu__toggle {
                display: none !important;
            }
            <?php endif; ?>

            /* Custom Dashboard Styling */
            .wrap h1 {
                color: #2c3e50; /* Darker heading color */
                font-size: 2em;
                margin-bottom: 20px;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }

            .welcome-panel {
                background: #ffffff;
                border: 1px solid #e0e0e0;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 0; /* Removed rounded corners */
            }

            .welcome-panel-content h2 {
                font-size: 1.8em;
                color: #34495e;
                margin-top: 0;
            }

            .welcome-panel-content p {
                font-size: 1.1em;
                line-height: 1.6;
                color: #555;
            }

            .welcome-panel-column-container {
                display: flex;
                flex-wrap: wrap; /* Allow columns to wrap on smaller screens */
                gap: 20px; /* Space between columns */
                margin-top: 20px;
            }

            .welcome-panel-column {
                flex: 1; /* Distribute space equally */
                min-width: 280px; /* Minimum width before wrapping */
                background: #f9f9f9;
                padding: 15px;
                border-radius: 0; /* Removed rounded corners */
                border: 1px solid #f0f0f0;
            }

            .welcome-panel-column h3 {
                font-size: 1.3em;
                color: #2980b9; /* Blue for section headings */
                margin-top: 0;
                margin-bottom: 15px;
                border-bottom: 1px solid #e9e9e9;
                padding-bottom: 10px;
            }

            .welcome-panel-column ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .welcome-panel-column ul li {
                margin-bottom: 10px;
            }

            .welcome-panel-column ul li a {
                text-decoration: none;
                color: #3498db; /* Link color */
                font-weight: 500;
                display: flex;
                align-items: center;
                transition: color 0.2s ease;
            }

            .welcome-panel-column ul li a:hover {
                color: #2980b9; /* Darker blue on hover */
            }

            .welcome-panel-column ul li a .dashicons {
                margin-right: 8px;
                color: #888; /* Icon color */
                font-size: 20px;
            }

            /* Dashboard Widgets Styling */
            #dashboard-widgets-wrap {
                margin-top: 30px;
            }

            #dashboard-widgets .postbox {
                background: #ffffff;
                border: 1px solid #e0e0e0;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                margin-bottom: 20px;
                border-radius: 0; /* Removed rounded corners */
            }

            #dashboard-widgets .postbox .hndle {
                background: #f7f7f7;
                border-bottom: 1px solid #e0e0e0;
                padding: 10px 15px;
                font-size: 1.2em;
                color: #444;
                cursor: default; /* Make handle non-draggable */
                border-top-left-radius: 0; /* Removed rounded corners */
                border-top-right-radius: 0; /* Removed rounded corners */
            }

            #dashboard-widgets .postbox .hndle span {
                font-weight: 600;
            }

            #dashboard-widgets .postbox .inside {
                padding: 15px;
                line-height: 1.6;
                color: #555;
            }

            /* Responsive Adjustments */
            @media screen and (max-width: 782px) {
                .welcome-panel-column-container {
                    flex-direction: column; /* Stack columns on small screens */
                }
                .welcome-panel-column {
                    min-width: auto; /* Remove min-width for stacking */
                }
            }
        </style>
        <?php
    }

    /**
     * Filters the text displayed in the admin footer (left side).
     *
     * @param string $footer_text The default footer text.
     * @return string The custom footer text, or the original if not set.
     */
    public function custom_admin_footer_text(string $footer_text): string {
        if (!empty($this->adminFooterText)) {
            // Only add version info if removeWpVersion is false, otherwise it's handled by update_footer filter
            if (!$this->removeWpVersion) {
                global $wp_version; // Access the global WordPress version variable
                $version_info = ' Version ' . $wp_version;
                return esc_html($this->adminFooterText . $version_info);
            }
            return esc_html($this->adminFooterText); // Return custom text without version if version is removed by other hook
        }
        return $footer_text;
    }

    /**
     * Filters the WordPress version text displayed in the admin footer (right side).
     *
     * @param string $version_text The default version text.
     * @return string An empty string to remove the version text.
     */
    public function remove_wp_version_from_footer(string $version_text): string {
        return ''; // Return an empty string to remove the version text
    }

    /**
     * Adds a custom dashboard page to the WordPress admin menu.
     * This page will serve as the replacement for the default dashboard.
     *
     * @return void
     */
    public function add_custom_dashboard_page(): void {
        add_menu_page(
            'Custom Dashboard', // Page title
            'Dashboard',        // Menu title (overwrites default Dashboard menu item)
            'read',             // Capability required to access the page
            $this->customDashboardSlug, // Menu slug
            array($this, 'custom_dashboard_page_content'), // Callback function to display the page content
            'dashicons-admin-home', // Icon URL or Dashicon class
            2 // Position in the menu order (just after Dashboard)
        );

        // Optionally, remove the default dashboard menu item
        remove_menu_page('index.php');
    }

    /**
     * Displays the content of the custom dashboard page.
     * You can put any HTML, PHP, or WordPress functions here to build your custom dashboard.
     *
     * @return void
     */
    public function custom_dashboard_page_content(): void {
        ?>
        <div class="wrap">
            <h1>Selamat Datang di Dashboard Kustom Anda!</h1>
            <p>Ini adalah dashboard kustom Anda. Anda dapat menambahkan widget, informasi, atau tautan penting di sini.</p>

            <div class="welcome-panel">
                <div class="welcome-panel-content">
                    <h2>Mulai dengan Situs Anda</h2>
                    <p class="about-description">Kami telah mengumpulkan beberapa tautan untuk membantu Anda memulai:</p>
                    <div class="welcome-panel-column-container">
                        <div class="welcome-panel-column">
                            <h3>Langkah Selanjutnya</h3>
                            <ul>
                                <li><a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="welcome-icon dashicons-media-document">Buat postingan baru</a></li>
                                <li><a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="welcome-icon dashicons-admin-page">Buat halaman baru</a></li>
                                <li><a href="<?php echo esc_url(admin_url('edit.php')); ?>" class="welcome-icon dashicons-admin-post">Lihat postingan Anda</a></li>
                                <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="welcome-icon dashicons-admin-page">Lihat halaman Anda</a></li>
                            </ul>
                        </div>
                        <div class="welcome-panel-column">
                            <h3>Informasi Lebih Lanjut</h3>
                            <ul>
                                <li><a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="welcome-icon dashicons-admin-users">Perbarui profil Anda</a></li>
                                <li><a href="<?php echo esc_url(admin_url('themes.php')); ?>" class="welcome-icon dashicons-admin-appearance">Sesuaikan tampilan situs Anda</a></li>
                                <li><a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="welcome-icon dashicons-admin-plugins">Kelola plugin Anda</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Example of adding a custom widget area
            // You would typically register sidebars/widgets in your functions.php
            // and then display them here.
            // For simplicity, this is just a placeholder.
            ?>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                            <div id="example_dashboard_widget" class="postbox">
                                <h2 class="hndle ui-sortable-handle"><span>Widget Kustom Contoh</span></h2>
                                <div class="inside">
                                    <p>Ini adalah widget kustom yang dapat Anda gunakan untuk menampilkan informasi penting.</p>
                                    <p>Misalnya, statistik situs, berita terbaru, atau tautan cepat.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Redirects the default WordPress dashboard to the custom dashboard page.
     * This hook runs when 'index.php' (the default dashboard) is loaded.
     *
     * @return void
     */
    public function redirect_dashboard(): void {
        if (get_current_screen()->base === 'dashboard') {
            wp_redirect(admin_url('admin.php?page=' . $this->customDashboardSlug));
            exit;
        }
    }
}

// Example of how to initialize the class with custom configurations:
// To use default settings (site name and link, no custom image, default footer text, keep WP version, no custom dashboard):
// new WpAdminBrandCustomizer();

// To use custom settings, including a custom dashboard:
new WpAdminBrandCustomizer([
    'admin_brand_text'      => 'Admin Perusahaan Saya', // Custom text for the admin bar
    'admin_brand_link_url'  => 'https://example.com/dashboard-kustom/', // Custom URL for the admin bar link
    'admin_footer_text'     => 'Ditenagai oleh Perusahaan Saya. Semua Hak Dilindungi.', // Custom text for the admin footer (left side)
    'remove_wp_version'     => true, // Set to true to remove the "Version X.X.X" text (right side)
    'custom_dashboard_slug' => 'dashboard-perusahaan', // Set a slug for your custom dashboard
]);
?>
