<?php

namespace NeonWebId\DevTools\Modules\MenuHider;

use NeonWebId\DevTools\Contracts\BaseModule;
use function __;
use function add_action;
use function get_option;
use function preg_replace;
use function sanitize_text_field;
use function update_option;
use function wp_verify_nonce;

final class MenuHider extends BaseModule
{
    private array $original_menu = [];
    private array $original_submenu = [];

    public function id(): string
    {
        return 'menu_hider';
    }

    public function title(): string
    {
        return __('Admin Menu Hider', 'dev-tools');
    }

    public function name(): string
    {
        return 'Menu Hider';
    }

    public function content(): void
    {
        // Save original menus to ensure the settings page always shows everything.
        $this->original_menu = $GLOBALS['menu'];
        $this->original_submenu = $GLOBALS['submenu'];

        $this->view->render('menu-hider/menu-hider', [
            'field'        => $this->field, // KIRIM OBJEK FIELD SECARA EKSPLISIT
            'all_menus'    => $this->get_all_menus_for_display(),
            'options'      => $this->get_hidden_menus(),
        ]);
    }

    public function apply(): void
    {
        // Handle form saving (this stays in the main module).
        $this->handle_form_submission();

        // DELEGATE the hiding logic to the handler.
        $hidden_menus = $this->get_hidden_menus();
        $handler = new MenuHiderHandler($hidden_menus);
        $handler->init();
    }

    private function handle_form_submission(): void
    {
        if ('POST' !== $_SERVER['REQUEST_METHOD'] || !isset($_POST['neonwebid_devtools_nonce']) || !isset($_POST['module']) || $this->id() !== $_POST['module']) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field($_POST['neonwebid_devtools_nonce']), 'neonwebid_devtools_save')) {
            die('Nonce verification failed!');
        }

        $selected_menus = isset($_POST[$this->id()]['hidden_menus']) ? array_map('sanitize_text_field', (array)$_POST[$this->id()]['hidden_menus']) : [];
        update_option('devtools_hidden_menus', $selected_menus);
    }

    protected function get_hidden_menus(): array
    {
        return get_option('devtools_hidden_menus', []);
    }

    protected function get_all_menus_for_display(): array
    {
        $menu_items = [];
        $excluded_slugs = ['separator1', 'separator2', 'separator-last', $GLOBALS['plugin_page']];

        foreach ($this->original_menu as $item) {
            $slug = $item[2];
            if (empty($item[0]) || in_array($slug, $excluded_slugs, true)) {
                continue;
            }

            $menu_items[$slug] = [
                'title' => $this->clean_menu_title($item[0]),
                'slug' => $slug,
                'sub' => [],
            ];

            if (!empty($this->original_submenu[$slug])) {
                foreach ($this->original_submenu[$slug] as $sub_item) {
                    $sub_slug = $sub_item[2];
                    if (empty($sub_item[0])) continue;
                    $menu_items[$slug]['sub'][$sub_slug] = [
                        'title' => $this->clean_menu_title($sub_item[0]),
                        'slug' => $sub_slug,
                    ];
                }
            }
        }
        return $menu_items;
    }

    private function clean_menu_title(string $title): string
    {
        return trim(preg_replace('/<span.*<\/span>/', '', $title));
    }
}