<?php

namespace NeonWebId\DevTools\Utils;

use JetBrains\PhpStorm\NoReturn;
use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Utils\ThemeUpdater\ThemeUpdater;

use function add_action;
use function add_menu_page;
use function add_query_arg;
use function add_submenu_page;
use function admin_url;
use function class_exists;
use function current_user_can;
use function defined;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_stylesheet_directory;
use function get_stylesheet_directory_uri;
use function in_array;
use function is_admin;
use function is_user_logged_in;
use function key;
use function sanitize_key;
use function strtolower;
use function submit_button;
use function wp_die;
use function wp_enqueue_media;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_get_current_user;
use function wp_nonce_field;
use function wp_redirect;
use function wp_slash;
use function wp_verify_nonce;

/**
 * Class Panel
 *
 * Handles the creation and management of a modular admin panel in WordPress.
 * This class provides the framework for adding option pages, handling form submissions,
 * and loading separate modules for different functionalities.
 *
 * @package NeonWebId\DevTools\Utils
 */
class Panel
{
    /**
     * The name to be displayed in the admin menu.
     * @var string
     */
    protected string $name = 'DevTools';

    /**
     * The title displayed in the panel page header.
     * @var string
     */
    protected string $title = 'DevTools';

    /**
     * The unique slug for the admin menu page.
     * @var string
     */
    protected string $page_slug = 'dev-tools';

    /**
     * The position of the menu in the WordPress admin sidebar.
     * @var int
     */
    protected int $position = 100;

    /**
     * Instance of DevOption for database interaction.
     * @var DevOption|null
     */
    protected ?DevOption $option = null;

    /**
     * Instance of Path for handling file paths.
     * @var Path|null
     */
    protected ?Path $path = null;

    /**
     * Instance of Uri for handling URIs.
     * @var Uri|null
     */
    protected ?Uri $uri = null;

    /**
     * Instance of View for rendering files and assets.
     * @var View|null
     */
    protected ?View $view = null;

    /**
     * Instance of ThemeUpdater for handling theme updates.
     * @var ThemeUpdater|null
     */
    protected ?ThemeUpdater $updater = null;

    /**
     * The key used to store data in the wp_options table.
     * @var string
     */
    protected string $optionName = '_dev_tools';

    /**
     * The base string for creating a security nonce.
     * @var string
     */
    protected string $nonce = 'dev_tools';

    /**
     * Array to hold initialized module instances.
     * @var BaseModule[]
     */
    private array $modules = [];

    /**
     * List of user logins or emails allowed to see this panel.
     * @var string[]
     */
    private array $showPanelFor = [];

    /**
     * Indicates whether the panel is in development mode.
     * This is true if WP_DEBUG is enabled or if the user is allowed to see the panel.
     * @var bool
     */
    private bool $isDevMode = false;

    /**
     * Indicates whether the current user can see the panel.
     * This is determined by the `showPanelFor` array or if WP_DEBUG is true.
     * @var bool
     */
    private bool $userCanSeePanel = false;

    /**
     * The general settings tab configuration.
     * This is used to render the general settings view.
     * It includes the title, name, and view file for the general settings tab.
     *
     * @var array
     */
    private array $generalTab = [
        'title' => 'General Settings',
        'name'  => 'General',
        'view'  => 'general', // The view file for the general settings tab: views/general.php
    ];

    /**
     * Panel constructor.
     * Declared final to prevent overriding. Initializes dependencies and boots the modules.
     */
    final public function __construct()
    {
        $baseDirectory = get_stylesheet_directory() . '/inc/dev-tools';
        $baseUri       = get_stylesheet_directory_uri() . '/inc/dev-tools';
        $this->path    = new Path($baseDirectory);
        $this->uri     = new Uri($baseUri);
        $this->view    = new View($this->path, $this->uri);
        $this->option  = new DevOption($this->optionName);
        $this->updater = new ThemeUpdater();

        $this->onConstruct();

        $this->initialized();
    }

    /**
     * Hook for child classes to run custom logic after the constructor.
     * Override this method in a child class to add custom initialization logic.
     *
     * @return void
     */
    protected function onConstruct(): void
    {
        // Intentionally left blank to be overridden.
    }

    /**
     * Defines the modules to be loaded by the panel.
     * This method should be overridden by a child class to register its specific modules.
     *
     * @return array<class-string<BaseModule>> List of module class names.
     */
    protected function modules(): array
    {
        return [];
    }

    /**
     * Initializes the panel by loading the modules defined in the `modules()` method.
     * This method is called from the constructor to ensure all modules are ready for use.
     *
     * @return void
     */
    private function initialized(): void
    {
        foreach ($this->modules() as $moduleClass) {
            if (class_exists($moduleClass)) {
                $module = new $moduleClass($this->view, $this->option);
                if ($module instanceof BaseModule) {
                    $this->modules[$module->id()] = $module;
                }
            }
        }
    }

    /**
     * Boots the panel by instantiating and storing the registered modules.
     * This method is called from the constructor.
     *
     * @return void
     */
    private function boot(): void
    {
        $this->isDevMode = defined('WP_DEBUG') && WP_DEBUG === true;

        if ($this->showPanelFor === []) {
            $this->userCanSeePanel = $this->isDevMode;
        } else {
            $user      = wp_get_current_user();
            $userLogin = strtolower($user->user_login);
            $userEmail = strtolower($user->user_email);

            $this->userCanSeePanel = in_array($userLogin, $this->showPanelFor, true)
                || in_array($userEmail, $this->showPanelFor, true);
        }
    }

    /**
     * Registers the WordPress hooks for the panel if conditions are met.
     * The panel is activated if WP_DEBUG is true or if the current user is allowed.
     *
     * @return void
     */
    private function panelRegistered(): void
    {
        if ( ! is_user_logged_in()) {
            return;
        }

        add_action('admin_menu', [$this, 'optionsPage']);
        add_action('admin_post_' . $this->page_slug, [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'scripts']);
    }

    /**
     * Sets the list of users (by username or email) who are allowed to see the panel.
     *
     * @param string[] $usernamesOrEmails An array of usernames or emails.
     *
     * @return void
     */
    final public function showPanelFor(array $usernamesOrEmails): void
    {
        $this->showPanelFor = array_map('strtolower', $usernamesOrEmails);
    }

    /**
     * Sets the general tab configuration for the panel.
     * This method allows customization of the general settings tab.
     *
     * @param array $tab An associative array with keys 'title', 'name', and 'view'.
     *                   - 'title': The title of the tab.
     *                   - 'name': The name of the tab.
     *                   - 'view': The view file to render for this tab.
     *
     * @return void
     */
    final public function setGeneralTab(array $tab): void
    {
        $this->generalTab = array_merge($this->generalTab, $tab);
    }

    /**
     * Enqueues scripts and styles for the admin panel page.
     * These are loaded only on the relevant page.
     *
     * @param string $hook_suffix The hook suffix of the current admin page.
     *
     * @return void
     */
    final public function scripts(string $hook_suffix): void
    {
        if (str_contains($hook_suffix, $this->page_slug)) {
            wp_enqueue_media();

            // Enqueue the color picker scripts and styles.
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            wp_enqueue_script(
                $this->page_slug . '-media-uploader',
                $this->uri->getAsset('js/wp-media-uploader.js'),
                ['jquery', 'wp-color-picker'],
                null,
                true
            );

            // field-dependencies.js
            wp_enqueue_script(
                $this->page_slug . '-field-dependencies',
                $this->uri->getAsset('js/field-dependencies.js'),
                ['jquery'],
                null,
                true
            );

            // repeater-field.js
            wp_enqueue_script(
                $this->page_slug . '-repeater-field',
                $this->uri->getAsset('js/repeater-field.js'),
                ['jquery', 'jquery-ui-sortable'],
                null,
                true
            );
        }

        wp_enqueue_style($this->page_slug, $this->uri->getAsset('/css/admin.css'));
        wp_enqueue_script($this->page_slug, $this->uri->getAsset('js/admin.js'), ['jquery'], null, true);
    }

    /**
     * Adds the main menu page and submenus to the WordPress admin dashboard.
     *
     * @return void
     */
    final public function optionsPage(): void
    {
        add_menu_page(
            $this->name,
            $this->title,
            'manage_options',
            $this->page_slug,
            '',
            'dashicons-admin-settings',
            $this->position
        );

        add_submenu_page(
            $this->page_slug,
            $this->generalTab['title'],
            $this->generalTab['name'],
            'manage_options',
            $this->page_slug,
            [$this, 'renderPage']
        );

        foreach ($this->modules as $module) {
            if ($this->isDevMode || $this->userCanSeePanel || $module->shouldAlwaysShow()) {
                $urlSubPage = add_query_arg([
                    'page' => $this->page_slug,
                    'tab'  => $module->id()
                ], 'admin.php');
                add_submenu_page($this->page_slug,
                    $module->title(),
                    $module->name(),
                    'manage_options',
                    $urlSubPage,
                    ''
                );
            }
        }

        if ($this->isDevMode || $this->userCanSeePanel) {
            $urlSubPage = add_query_arg([
                'page' => $this->page_slug,
                'tab'  => 'system-info'
            ], 'admin.php');
            add_submenu_page(
                $this->page_slug,
                'System Info',
                'System Info',
                'manage_options',
                $urlSubPage,
                ''
            );
        }
    }

    /**
     * Renders the HTML content for the main options page.
     * Manages the display of tabs and their content.
     *
     * @return void
     */
    final public function renderPage(): void
    {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->title); ?></h1>
            <?php
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                echo '<div id="message" class="updated notice is-dismissible"><p>Settings saved.</p></div>';
            }
            ?>

            <h2 class="nav-tab-wrapper">
                <?php
                $general_tab_url = add_query_arg([
                    'page' => $this->page_slug
                ], admin_url('admin.php'));
                ?>
                <a href="<?php echo esc_url($general_tab_url); ?>"
                   class="nav-tab <?php echo($active_tab ===
                   'general' ? 'nav-tab-active' : ''); ?>"><?php echo $this->generalTab['name']; ?></a>

                <?php
                foreach ($this->modules as $module):
                    if ($this->isDevMode || $this->userCanSeePanel || $module->shouldAlwaysShow()) :

                        $module_tab_url = add_query_arg([
                            'page' => $this->page_slug,
                            'tab'  => $module->id()
                        ], admin_url('admin.php')); ?>
                        <a href="<?php echo esc_url($module_tab_url); ?>"
                           class="nav-tab <?php echo($active_tab ===
                           $module->id() ? 'nav-tab-active' : ''); ?>"><?php echo esc_html($module->name()); ?></a>
                    <?php endif; ?>

                <?php endforeach; ?>

                <?php if ($this->isDevMode || $this->userCanSeePanel) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['page' => $this->page_slug, 'tab' => 'system-info'],
                        admin_url('admin.php'))); ?>"
                       class="nav-tab <?php echo($active_tab === 'system-info' ? 'nav-tab-active' : ''); ?>">System
                        Info</a>
                <?php endif; ?>
            </h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr($this->page_slug); ?>">
                <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">
                <?php wp_nonce_field('my_theme_save_options_action', $this->nonce . '_nonce'); ?>

                <div class="tab-content">
                    <?php if ($active_tab === 'general') : ?>
                        <h3><?php echo $this->generalTab['title']; ?></h3>
                        <?php $this->view->render($this->generalTab['view']); ?>
                    <?php elseif (
                        isset($this->modules[$active_tab])
                        &&
                        $this->modules[$active_tab] instanceof BaseModule
                        &&
                        ($this->isDevMode || $this->userCanSeePanel || $this->modules[$active_tab]->shouldAlwaysShow())
                    ) : ?>
                        <h3><?php echo esc_html($this->modules[$active_tab]->title()); ?></h3>
                        <?php $this->modules[$active_tab]->content(); ?>
                    <?php elseif ($active_tab === 'system-info'): ?>
                        <h3><?php echo esc_html('System Information'); ?></h3>
                        <?php
                        $this->view->render('system-info', [
                            'theme'   => wp_get_theme(),
                            'plugins' => get_option('active_plugins', [])
                        ]);
                        ?>
                    <?php else: ?>
                        <div class="notice notice-error">
                            <p><?php echo esc_html('The requested tab does not exist or is not accessible.'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! in_array($active_tab, ['general', 'system-info'])) : ?>
                    <?php submit_button(); ?>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handles form submission and saves the options.
     * Includes nonce and user capability validation.
     *
     * @return void
     */
    #[NoReturn]
    final public function save(): void
    {
        if ( ! isset($_POST[$this->nonce . '_nonce']) ||
            ! wp_verify_nonce($_POST[$this->nonce . '_nonce'], 'my_theme_save_options_action')
        ) {
            wp_die('Security: Invalid nonce!');
        }

        if ( ! current_user_can('manage_options')) {
            wp_die('You do not have permission to do this.');
        }

        $options = $this->option->getAll() ?: [];

        $redirect_url = add_query_arg(
            [
                'page'             => $this->page_slug,
                'tab'              => isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'general',
                'settings-updated' => 'false'
            ],
            admin_url('admin.php')
        );

        // Merge the submitted data with existing options.
        if (isset($_POST['_dev_tools']) && is_array($_POST['_dev_tools'])) {
            $postData = wp_slash($_POST['_dev_tools']);
            if ( ! empty($postData)) {
                $id           = key($postData);
                $options[$id] = $postData[$id];
            }

            $this->option->setBatch($options);

            $redirect_url = add_query_arg(
                [
                    'page'             => $this->page_slug,
                    'tab'              => isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'general',
                    'settings-updated' => 'true'
                ],
                admin_url('admin.php')
            );
        }

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * The main entry point to activate the panel and its modules.
     * This method registers all necessary WordPress hooks.
     *
     * @return void
     */
    final public function apply(): void
    {
        foreach ($this->modules as $module) {
            $module->apply();
        }

        $this->updater?->init();

        if ( ! is_admin()) {
            return;
        }

        $this->boot();
        $this->panelRegistered();
    }
}