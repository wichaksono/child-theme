<?php

namespace NeonWebId\DevTools\Modules\MenuHider;

use function in_array;
use function remove_menu_page;
use function remove_submenu_page;

/**
 * Handles the logic of hiding WordPress admin menus based on provided options.
 */
final class MenuHiderHandler
{
    /**
     * The list of menu and submenu slugs to hide.
     * @var array
     */
    private array $hidden_menus;

    /**
     * Constructor.
     *
     * @param array $hidden_menus An array of menu slugs to be hidden.
     */
    public function __construct(array $hidden_menus)
    {
        $this->hidden_menus = $hidden_menus;
    }

    /**
     * Initializes the hooks to hide the menus.
     */
    public function init(): void
    {
        if (empty($this->hidden_menus)) {
            return;
        }
        // Hook into admin_menu with a very late priority to ensure all menus are registered.
        add_action('admin_menu', [$this, 'hide_selected_menus'], 9999);
    }

    /**
     * The core function that iterates through menus and submenus to hide them.
     * This method is public so it can be called by the add_action hook.
     */
    public function hide_selected_menus(): void
    {
        global $menu, $submenu;

        // Hide parent menus
        foreach ($menu as $menu_item) {
            $slug = $menu_item[2];
            if (in_array($slug, $this->hidden_menus, true)) {
                remove_menu_page($slug);
            }
        }

        // Hide submenus
        foreach ($submenu as $parent_slug => $sub_items) {
            foreach ($sub_items as $sub_item) {
                $sub_slug = $sub_item[2];
                if (in_array($sub_slug, $this->hidden_menus, true)) {
                    remove_submenu_page($parent_slug, $sub_slug);
                }
            }
        }
    }
}