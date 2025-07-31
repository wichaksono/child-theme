<?php

namespace NeonWebId\DevTools\Modules\MenuHider;

use NeonWebId\DevTools\Contracts\BaseModule;

use function __;
use function add_action;
use function ksort;
use function preg_replace;
use function print_r;
use function var_dump;

final class MenuHider extends BaseModule
{
    private array $original_menu = [];
    private array $original_submenu = [];

    protected function onContructor(): void
    {
        add_action('admin_menu', function () {
            global $menu, $submenu;
            $this->original_menu    = $menu;
            $this->original_submenu = $submenu;

            ksort($this->original_menu);
        });
    }

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
        $this->view->render('menu-hider/menu-hider', [
            'field'     => $this->field, // KIRIM OBJEK FIELD SECARA EKSPLISIT
            'all_menus' => $this->get_all_menus_for_display(),
            'options'   => $this->get_hidden_menus(),
        ]);
    }

    public function apply(): void
    {
        // DELEGATE the hiding logic to the handler.
        $hidden_menus = $this->get_hidden_menus();

        $handler = new MenuHiderHandler($hidden_menus);
        $handler->init();
    }

    public function get_hidden_menus(): array
    {
        return $this->option->get('menu_hider', []);
    }

    protected function get_all_menus_for_display(): array
    {
        $menu_items     = [];
        $excluded_slugs = [
            'separator1',
            'separator2',
            'separator-last',
            'edit-tags.php?taxonomy=link_category',
            $GLOBALS['plugin_page']
        ];
        foreach ($this->original_menu as $item) {
            $slug = $item[2];
            if (empty($item[0]) || in_array($slug, $excluded_slugs, true)) {
                continue;
            }

            $menu_items[$slug] = [
                'title' => $this->clean_menu_title($item[0]),
                'slug'  => $slug,
                'sub'   => [],
            ];

            if ( ! empty($this->original_submenu[$slug])) {
                foreach ($this->original_submenu[$slug] as $sub_item) {
                    $sub_slug = $sub_item[2];
                    if (empty($sub_item[0])) {
                        continue;
                    }
                    $menu_items[$slug]['sub'][$sub_slug] = [
                        'title' => $this->clean_menu_title($sub_item[0]),
                        'slug'  => $sub_slug,
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