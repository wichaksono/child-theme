<?php

namespace NeonWebId\DevTools\Utils;

use JetBrains\PhpStorm\NoReturn;
use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Utils\ThemeUpdater\ThemeUpdater;

use WP_Admin_Bar;

use function add_action;
use function add_menu_page;
use function add_query_arg;
use function add_submenu_page;
use function admin_url;
use function current_user_can;
use function defined;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_stylesheet_directory;
use function get_stylesheet_directory_uri;
use function get_stylesheet_uri;
use function in_array;
use function is_admin;
use function sanitize_key;
use function submit_button;
use function wp_die;
use function wp_enqueue_media;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_get_current_user;
use function wp_nonce_field;
use function wp_redirect;
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

    private bool $isDevMode = false;

    /**
     * Panel constructor.
     * Declared final to prevent overriding. Initializes dependencies and boots the modules.
     */
    final public function __construct()
    {
        $baseDirectory = get_stylesheet_directory() . '/inc/dev-tools';
        $baseUri       = get_stylesheet_directory_uri() . '/inc/dev-tools';
        $this->view    = new View($baseDirectory, $baseUri);
        $this->option  = new DevOption($this->optionName);
        $this->updater = new ThemeUpdater();

        $this->onConstruct();

        $this->boot();
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
     * Boots the panel by instantiating and storing the registered modules.
     * This method is called from the constructor.
     *
     * @return void
     */
    private function boot(): void
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
            wp_enqueue_style($this->page_slug, $this->view->getAsset('/css/admin.css'));
            wp_enqueue_media();
            wp_enqueue_script($this->page_slug, $this->view->getAsset('js/admin.js'), ['jquery'], null, true);
        }
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
            'General',
            'General',
            'manage_options',
            $this->page_slug,
            [$this, 'renderPage']
        );

        foreach ($this->modules as $module) {
            add_submenu_page(
                $this->page_slug,
                $module->title(),
                $module->name(),
                'manage_options',
                // URL slug for the submenu
                add_query_arg(['page' => $this->page_slug, 'tab' => $module->id()], 'admin.php'),
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
        $options    = $this->option->getAll() ?: [];
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
                $general_tab_url = add_query_arg(['page' => $this->page_slug, 'tab' => 'general'],
                    admin_url('admin.php'));
                ?>
                <a href="<?php echo esc_url($general_tab_url); ?>"
                   class="nav-tab <?php echo($active_tab === 'general' ? 'nav-tab-active' : ''); ?>">General</a>

                <?php
                foreach ($this->modules as $module):
                    $module_tab_url = add_query_arg(['page' => $this->page_slug, 'tab' => $module->id()],
                        admin_url('admin.php'));
                    ?>
                    <a href="<?php echo esc_url($module_tab_url); ?>"
                       class="nav-tab <?php echo($active_tab ===
                       $module->id() ? 'nav-tab-active' : ''); ?>"><?php echo esc_html($module->name()); ?></a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr($this->page_slug); ?>">
                <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">
                <?php wp_nonce_field('my_theme_save_options_action', $this->nonce . '_nonce'); ?>

                <div class="tab-content">
                    <?php if ($active_tab === 'general') : ?>
                        <h3>General</h3>
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th scope="row"><label for="site_description">Site Description</label></th>
                                <td>
                                    <input type="text" id="site_description" name="site_description"
                                           value="<?php echo isset($options['site_description']) ? esc_attr($options['site_description']) : ''; ?>"
                                           class="regular-text">
                                    <p class="description">Enter a short description for your site.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="copyright_text">Copyright Text</label></th>
                                <td>
                                    <input type="text" id="copyright_text" name="copyright_text"
                                           value="<?php echo isset($options['copyright_text']) ? esc_attr($options['copyright_text']) : ''; ?>"
                                           class="regular-text">
                                    <p class="description">Enter the copyright text for the footer.</p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    <?php elseif (
                        isset($this->modules[$active_tab])
                        && $this->modules[$active_tab] instanceof BaseModule
                    ) : ?>
                        <h3><?php echo esc_html($this->modules[$active_tab]->title()); ?></h3>
                        <?php $this->modules[$active_tab]->content(); ?>
                    <?php endif; ?>
                </div>

                <?php submit_button(); ?>
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

        // TODO: Add sanitization logic for data from $_POST before saving.

        $this->option->setBatch($options);

        $redirect_url = add_query_arg(
            [
                'page'             => $this->page_slug,
                'tab'              => isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'general',
                'settings-updated' => 'true'
            ],
            admin_url('admin.php')
        );
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
        $this->panelRegistered();

        foreach ($this->modules as $module) {
            $module->apply();
        }

        $this->updater?->init();
    }

    /**
     * Registers the WordPress hooks for the panel if conditions are met.
     * The panel is activated if WP_DEBUG is true or if the current user is allowed.
     *
     * @return void
     */
    private function panelRegistered(): void
    {
        if ( !is_admin() ) {
            return;
        }

        $debugMode = defined('WP_DEBUG') && WP_DEBUG === true;

        $user        = wp_get_current_user();
        $userLogin   = strtolower($user->user_login);
        $userEmail   = strtolower($user->user_email);
        $allowedUser = in_array($userLogin, $this->showPanelFor, true)
            || in_array($userEmail, $this->showPanelFor, true);

        if ($debugMode || $allowedUser) {
            add_action('admin_menu', [$this, 'optionsPage']);
            add_action('admin_post_' . $this->page_slug, [$this, 'save']);
            add_action('admin_enqueue_scripts', [$this, 'scripts']);
        }
    }
}