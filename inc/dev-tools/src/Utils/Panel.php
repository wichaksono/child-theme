<?php

namespace NeonWebId\DevTools\Utils;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Utils\ThemeUpdater\ThemeUpdater;

use function add_action;
use function add_menu_page;
use function add_query_arg;
use function add_submenu_page;
use function admin_url;
use function current_user_can;
use function esc_attr;
use function esc_url;
use function get_stylesheet_directory;
use function get_stylesheet_uri;
use function sanitize_key;
use function submit_button;
use function wp_die;
use function wp_enqueue_media;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_nonce_field;
use function wp_redirect;
use function wp_verify_nonce;

class Panel
{
    protected string $name = 'DevTools';
    protected string $title = 'DevTools';
    protected string $page_slug = 'dev-tools';
    protected int $position = 100;

    protected ?DevOption $option = null;
    protected ?View $view = null;
    protected ?ThemeUpdater $updater = null;

    protected string $optionName = '_dev_tools';
    protected string $nonce = 'dev_tools';

    /**
     * @var BaseModule[]
     */
    private array $modules = [];

    private array $boots;

    final public function __construct()
    {
        $baseDirectory = get_stylesheet_directory() . '/inc/dev-tools';
        $baseUri       = get_stylesheet_uri() . '/inc/dev-tools';
        $this->view    = new View($baseDirectory, $baseUri);
        $this->option  = new DevOption($this->optionName);
        $this->updater = new ThemeUpdater();

        add_action('admin_menu', [$this, 'optionsPage']);
        add_action('admin_post_' . $this->page_slug, [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'scripts']);

        $this->onConstruct();

        $this->boots = $this->register();

        $this->boot();
    }

    protected function onConstruct(): void
    {
        // Override this method in child classes to add custom initialization logic.
    }

    protected function register(): array
    {
        return [];
    }

    private function boot(): void
    {
        // Override this method in child classes to perform additional bootstrapping.
        foreach ($this->boots as $boot) {
            if (class_exists($boot)) {
                $module = new $boot($this->view, $this->option);
                if ($module instanceof BaseModule) {
                    $this->modules[$module->id()] = $module;
                }
            }
        }
    }

    /**
     * Enqueue admin scripts and styles.
     */
    final public function scripts($hook_suffix): void
    {
        if (str_contains($hook_suffix, $this->page_slug)) {
            wp_enqueue_style($this->page_slug, $this->view->getAsset('/css/admin.css'));
            wp_enqueue_media();
            wp_enqueue_script($this->page_slug, $this->view->getAsset('js/admin.js'), ['jquery'], null, true);
        }
    }

    /**
     * Add the main theme options page and submenus.
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
                $this->page_slug . '&tab=' . $module->id(),
                ''
            );
        }
    }

    /**
     * Handle form submission and save theme options.
     */
    #[NoReturn]
    final public function save(): void
    {
        if ( ! isset($_POST[$this->nonce . '_nonce']) ||
            ! wp_verify_nonce($_POST[$this->nonce . '_nonce'], 'my_theme_save_options_action')
        ) {
            wp_die('Keamanan: Nonce tidak valid!');
        }

        if ( ! current_user_can('manage_options')) {
            wp_die('Anda tidak memiliki izin untuk melakukan ini.');
        }

        $options = $this->option->getAll() ?: [];

        // Sanitize and save the options

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
     * Render the main theme options page content.
     */
    final public function renderPage(): void
    {
        $options = $this->option->getAll() ?: [];

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php echo $this->title; ?></h1>
            <?php
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                echo '<div id="message" class="updated notice is-dismissible"><p>Pengaturan disimpan.</p></div>';
            }
            ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=general"
                   class="nav-tab <?php echo($active_tab === 'general' ? 'nav-tab-active' : ''); ?>">General</a>

                <?php
                foreach ($this->modules as $module):
                    ?>
                    <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=<?php echo $module->id(); ?>"
                       class="nav-tab <?php echo($active_tab ===
                       $module->id() ? 'nav-tab-active' : ''); ?>"><?php echo $module->name(); ?></a>
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
                                <th scope="row"><label for="site_description">Deskripsi Situs</label></th>
                                <td>
                                    <input type="text" id="site_description" name="site_description"
                                           value="<?php echo isset($options['site_description']) ? esc_attr($options['site_description']) : ''; ?>"
                                           class="regular-text">
                                    <p class="description">Masukkan deskripsi singkat untuk situs Anda.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="copyright_text">Teks Hak Cipta</label></th>
                                <td>
                                    <input type="text" id="copyright_text" name="copyright_text"
                                           value="<?php echo isset($options['copyright_text']) ? esc_attr($options['copyright_text']) : ''; ?>"
                                           class="regular-text">
                                    <p class="description">Masukkan teks hak cipta untuk footer.</p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    <?php elseif (
                        isset($this->modules[$active_tab])
                        && $this->modules[$active_tab] instanceof BaseModule
                    ) : ?>
                        <h3><?php echo $this->modules[$active_tab]->title(); ?></h3>
                        <?php $this->modules[$active_tab]->content(); ?>
                    <?php endif; ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @throws Exception
     */
    final public function run(): void
    {
        foreach ($this->modules as $module) {
            $module->apply();
        }

        $this->updater?->init();
    }
}