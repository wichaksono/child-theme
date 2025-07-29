<?php

/**
 * Bootstrap file for the NeonWebID theme options manual.
 */

use JetBrains\PhpStorm\NoReturn;
use NeonWebId\DevTools\Contracts\Base;
use NeonWebId\DevTools\Modules\Brand;
use NeonWebId\DevTools\Utils\DevOption;
use NeonWebId\DevTools\Utils\View;

return new class {

    private string $name = 'DevTools';
    private string $title = 'DevTools';
    private string $page_slug = 'dev-tools';
    private int $position = 100;

    private ?DevOption $option = null;
    private ?View $view = null;

    private string $optionName = 'my_theme_manual_options';

    private array $modules = [];

    private array $boots = [
        Brand::class,
    ];

    public function __construct()
    {
        spl_autoload_register([$this, 'loadClass']);

        $this->view   = new View(__DIR__, get_stylesheet_uri() . '/inc/dev-tools');
        $this->option = new DevOption($this->optionName);

        add_action('admin_menu', array($this, 'add_theme_options_page'));
        add_action('admin_post_my_theme_save_options_manual',
            array($this, 'save_theme_options'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));


    }

    public function loadClass($className): void
    {
        $baseNamespce = 'NeonWebId\\DevTools\\';
        $len          = strlen($baseNamespce);
        if (strncmp($baseNamespce, $className, $len) !== 0) {
            return;
        }
        $className = substr($className, $len);
        $file = __DIR__ . '/src/' . str_replace('\\', '/', $className) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook_suffix): void
    {
        if (str_contains($hook_suffix, $this->page_slug)) {
            wp_enqueue_style('my-theme-options-manual-style',
                $this->view->getAsset('/css/my-theme-options-manual.css'));
            wp_enqueue_media();
            wp_enqueue_script('my-theme-options-manual-script',
                $this->view->getAsset('js/my-theme-options-manual.js'), array('jquery'), null,
                true);
        }
    }

    /**
     * Add the main theme options page and submenus.
     */
    public function add_theme_options_page(): void
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
            [$this, 'render_theme_options_page']
        );

        foreach ($this->boots as $boot) {
            $module = new $boot($this->view, $this->option);

            $this->modules[$module->id()] = $module;

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
    public function save_theme_options(): void
    {
        if ( ! isset($_POST['my_theme_options_nonce']) ||
            ! wp_verify_nonce($_POST['my_theme_options_nonce'], 'my_theme_save_options_action')
        ) {
            wp_die('Keamanan: Nonce tidak valid!');
        }

        if ( ! current_user_can('manage_options')) {
            wp_die('Anda tidak memiliki izin untuk melakukan ini.');
        }

        $options = get_option($this->optionName, array());

        if (isset($_POST['site_description'])) {
            $options['site_description'] = sanitize_text_field($_POST['site_description']);
        }
        if (isset($_POST['copyright_text'])) {
            $options['copyright_text'] = sanitize_text_field($_POST['copyright_text']);
        }
        if (isset($_POST['header_logo_url'])) {
            $options['header_logo_url'] = esc_url_raw($_POST['header_logo_url']);
        }
        $options['show_search_bar'] = (isset($_POST['show_search_bar']) &&
            $_POST['show_search_bar'] === '1') ? '1' : '0';
        if (isset($_POST['social_links'])) {
            $options['social_links'] = sanitize_textarea_field($_POST['social_links']);
        }
        if (isset($_POST['contact_email'])) {
            $options['contact_email'] = sanitize_email($_POST['contact_email']);
        }

        update_option($this->optionName, $options);

        $redirect_url = add_query_arg(
            array(
                'page'             => $this->page_slug,
                'tab'              => isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'general',
                'settings-updated' => 'true'
            ),
            admin_url('admin.php')
        );
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Render the main theme options page content.
     */
    public function render_theme_options_page(): void
    {
        $options = get_option($this->optionName, array());

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
                   class="nav-tab <?php echo($active_tab === 'general' ? 'nav-tab-active' : ''); ?>">Umum</a>

                <?php
                foreach ($this->modules as $module):
                ?>
                    <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=<?php echo $module->id();?>"
                       class="nav-tab <?php echo($active_tab === $module->id() ? 'nav-tab-active' : ''); ?>"><?php echo $module->name();?></a>
                <?php endforeach;?>
            </h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="my_theme_save_options_manual">
                <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">
                <?php wp_nonce_field('my_theme_save_options_action', 'my_theme_options_nonce'); ?>

                <div class="tab-content">
                    <?php if ($active_tab === 'general') : ?>
                        <h3>Pengaturan Umum</h3>
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
                    <?php elseif ( isset($this->modules[$active_tab]) && $this->modules[$active_tab] instanceof Base) : ?>
                       <h3><?php $this->modules[$active_tab]->name();?></h3>
                        <?php $this->modules[$active_tab]->content();?>
                    <?php endif; ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#header_logo_url_button').click(function (e) {
                    e.preventDefault();
                    var button = $(this);
                    var field = $('#header_logo_url');
                    var preview = $('#header_logo_url_preview');

                    var custom_uploader = wp.media({
                        title: 'Pilih Gambar Logo',
                        library: {
                            type: 'image'
                        },
                        button: {
                            text: 'Pilih Gambar'
                        },
                        multiple: false
                    }).on('select', function () {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        field.val(attachment.url);
                        preview.html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">');
                    }).open();
                });
            });
        </script>
        <?php
    }
};

