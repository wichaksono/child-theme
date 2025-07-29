<?php
/**
 * Custom Menu Selector Functionality for Theme's functions.php
 * This code provides an admin page to select and manage WordPress admin menus and submenus.
 * It's designed to be placed directly in your theme's functions.php file.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * MenuSelectorHandler Class
 * Manages the custom admin menu page for selecting menu and submenu items.
 * Designed to be used within a theme's functions.php.
 */
class MenuSelectorHandler {

    /**
     * @var string Stores the message to be displayed (success/error).
     */
    private $message = '';

    /**
     * @var string Stores the type of message ('success' or 'error').
     */
    private $message_type = '';

    /**
     * @var array Stores the original menu before any modifications.
     */
    private $original_menu = array();

    /**
     * @var array Stores the original submenu before any modifications.
     */
    private $original_submenu = array();

    /**
     * Constructor
     * Hooks into WordPress actions to initialize the functionality.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu_page'));
        // Handle form submission BEFORE admin_menu hook
        add_action('init', array($this, 'handle_form_submission'));
        // Save original menus before any modifications
        add_action('admin_menu', array($this, 'save_original_menus'), 999);
        // Hook into admin_menu with a high priority to unset menus after they are all registered
        add_action('admin_menu', array($this, 'unset_menus_from_display'), 1000);
    }

    /**
     * Saves the original menu and submenu before any modifications.
     */
    public function save_original_menus(): void
    {
        global $menu, $submenu;

        // Create deep copies to preserve original state
        $this->original_menu = $menu;
        $this->original_submenu = $submenu;
    }

    /**
     * Adds the custom menu page to the WordPress admin.
     */
    public function add_admin_menu_page(): void
    {
        add_menu_page(
            __('Menu Selector', 'textdomain'), // Page title
            __('Menu Selector', 'textdomain'), // Menu title
            'manage_options',                  // Capability required to access
            'menu-selector-page',              // Menu slug
            array($this, 'render_menu_selector_page'), // Callback function to render the page
            'dashicons-list-view',             // Icon URL or Dashicon class
            80                                 // Position in the menu order
        );
    }

    /**
     * Handles the form submission for saving menu selections.
     * This method is called early via 'init' hook to ensure it runs before admin_menu.
     */
    public function handle_form_submission(): void
    {
        // Only process in admin area and if form is submitted
        if (!is_admin() || !isset($_POST['save_menu_selections_nonce'])) {
            return;
        }

        // Only process if we are on our specific admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'menu-selector-page') {
            return;
        }

        if (!wp_verify_nonce($_POST['save_menu_selections_nonce'], 'save_menu_selections_action')) {
            $this->message = __('Invalid security nonce. Please try again.', 'textdomain');
            $this->message_type = 'error';
            return;
        }

        if (!current_user_can('manage_options')) {
            $this->message = __('You do not have permission to perform this action.', 'textdomain');
            $this->message_type = 'error';
            return;
        }

        $selected_menus = isset($_POST['selected_menus']) ? array_map('sanitize_text_field', (array)$_POST['selected_menus']) : array();

        // Save the selections to WordPress options
        if (update_option('menu_selector_saved_menus', $selected_menus)) {
            $this->message = __('Menu selections saved successfully!', 'textdomain');
            $this->message_type = 'success';

            // Redirect to prevent resubmission and ensure fresh page load
            wp_redirect(admin_url('admin.php?page=menu-selector-page&updated=1'));
            exit;
        } else {
            $this->message = __('Failed to save selections. Perhaps no changes were made or an error occurred.', 'textdomain');
            $this->message_type = 'error';
        }
    }

    /**
     * Unsets (hides) menus and submenus from the admin display based on saved selections.
     * This method runs at a late priority on the 'admin_menu' hook to ensure all menus are registered.
     */
    public function unset_menus_from_display(): void
    {
        // Ensure this logic only applies in the admin area and for users with 'manage_options' capability.
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        global $menu, $submenu;

        // Get the saved menu selections from WordPress options
        $saved_selections = get_option('menu_selector_saved_menus', array());

        // If no selections saved, don't hide anything
        if (empty($saved_selections)) {
            return;
        }

        // --- Process Main Menus ---
        // Iterate through a copy of the main menu array to safely unset items
        $temp_menu = $menu;
        foreach ($temp_menu as $key => $item) {
            $menu_slug = $item[2];

            // If the menu slug IS found in the saved selections, remove it (because "checked should disappear")
            if (in_array($menu_slug, $saved_selections)) {
                unset($menu[$key]); // Remove the main menu item
                // Also, ensure its corresponding submenus are removed if the parent is hidden
                if (isset($submenu[$menu_slug])) {
                    unset($submenu[$menu_slug]);
                }
            }
        }

        // --- Process Submenus ---
        // Iterate through each main menu's submenus
        $temp_submenu = $submenu; // Use a temporary copy for iteration
        foreach ($temp_submenu as $menu_slug => $sub_items) {
            // Check if the parent menu still exists (it might have been unset above)
            $parent_menu_exists = false;
            foreach ($menu as $main_menu_item) {
                if ($main_menu_item[2] === $menu_slug) {
                    $parent_menu_exists = true;
                    break;
                }
            }

            // Only process submenus if their parent menu is still visible
            if ($parent_menu_exists) {
                $temp_sub_items = $sub_items; // Temporary copy for sub_items
                foreach ($temp_sub_items as $sub_key => $sub_item) {
                    $sub_slug = $sub_item[2];
                    // If the submenu slug IS found in the saved selections, remove it
                    if (in_array($sub_slug, $saved_selections)) {
                        unset($submenu[$menu_slug][$sub_key]);
                    }
                }
                // After filtering submenus, if a parent menu now has no submenus left,
                // and it wasn't removed because its own slug was selected,
                // we should remove the parent menu if it's empty of submenus.
                if (isset($submenu[$menu_slug]) && empty($submenu[$menu_slug])) {
                    // Only remove the parent if it wasn't explicitly selected to be kept visible
                    foreach ($menu as $menu_key => $menu_item) {
                        if ($menu_item[2] === $menu_slug) {
                            // Only unset if the parent itself was not in the original saved_selections
                            if (!in_array($menu_slug, $saved_selections)) {
                                unset($menu[$menu_key]);
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Maintain proper menu order by sorting by key instead of re-indexing
        ksort($menu);
    }

    /**
     * Clean menu title - removes span tags AND their content
     */
    private function clean_menu_title($title) {
        static $cache = array();

        $title = (string) $title;
        $cache_key = md5($title);

        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        // Remove span tags AND their content (including nested spans)
        while (preg_match('/<span[^>]*>.*?<\/span>/is', $title)) {
            $title = preg_replace('/<span[^>]*>.*?<\/span>/is', '', $title);
        }

        // Remove any other HTML tags (but keep their content)
        $title = strip_tags($title);

        // Clean up whitespace
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);

        $cache[$cache_key] = $title;
        return $title;
    }

    /**
     * Renders the custom menu selector page content, including inline CSS and JS.
     * Uses original menu data to always show all available menus.
     */
    public function render_menu_selector_page(): void
    {
        // Check if we're showing a success message from redirect
        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            $this->message = __('Menu selections saved successfully!', 'textdomain');
            $this->message_type = 'success';
        }

        // Use original menu data instead of potentially modified global menu
        $menu = $this->original_menu;
        $submenu = $this->original_submenu;

        // If original menus are empty (shouldn't happen), fall back to global
        if (empty($menu)) {
            global $menu, $submenu;
        }

        // Sort menu by key (priority/index) to maintain WordPress order
        ksort($menu);

        // Filter out unwanted menu items (separators, Links, Link Categories, Menu Selector) while preserving keys
        $filtered_menu = array();
        $excluded_slugs = array(
            'separator1',
            'separator2',
            'separator-last',
            'link-manager.php',                // Links menu
            'edit-tags.php?taxonomy=link_category',  // Link Categories
            'menu-selector-page'               // Menu Selector (this plugin's menu)
        );

        foreach ($menu as $key => $item) {
            if (!empty($item[0]) && !in_array($item[2], $excluded_slugs)) {
                $filtered_menu[$key] = $item;
            }
        }

        // Keep the menu order by maintaining keys and sorting by priority
        ksort($filtered_menu);

        // Handle the case where $filtered_menu might be empty to prevent array_chunk error
        $menu_chunks = [];
        if (!empty($filtered_menu)) {
            $menu_chunks = array_chunk($filtered_menu, ceil(count($filtered_menu) / 3), true); // Preserve keys
        }

        // Get saved selections
        $saved_selections = get_option('menu_selector_saved_menus', array());
        ?>
        <div class="wrap menu-selector-wrap">
            <h1><?php esc_html_e('Pilih Menu & Submenu Admin', 'textdomain'); ?></h1>
            <p class="description"><?php esc_html_e('Pilih item menu dan submenu admin yang ingin Anda sembunyikan. Menu yang dicentang akan disembunyikan dari area admin.', 'textdomain'); ?></p>

            <?php if ($this->message): // Use class property for message ?>
                <div class="notice notice-<?php echo esc_attr($this->message_type); ?> is-dismissible">
                    <p><?php echo esc_html($this->message); ?></p>
                </div>
            <?php endif; ?>

            <form id="menu-selector-form" method="post" action="">
                <?php wp_nonce_field('save_menu_selections_action', 'save_menu_selections_nonce'); ?>

                <div class="button-controls">
                    <button type="button" id="select-all" class="button button-primary"><?php esc_html_e('Pilih Semua', 'textdomain'); ?></button>
                    <button type="button" id="deselect-all" class="button button-secondary"><?php esc_html_e('Hapus Semua', 'textdomain'); ?></button>
                    <button type="submit" id="save-selections" class="button button-hero button-primary right-aligned"><?php esc_html_e('Simpan Pilihan', 'textdomain'); ?></button>
                </div>

                <div class="menu-columns-container">
                    <?php foreach ($menu_chunks as $menu_part): ?>
                        <div class="menu-column">
                            <ul class="menu-list">
                                <?php foreach ($menu_part as $priority => $item): ?>
                                    <?php
                                    $menu_slug = $item[2];
                                    $menu_title = $this->clean_menu_title($item[0]);
                                    $is_menu_selected = in_array($menu_slug, $saved_selections);
                                    ?>
                                    <li class="menu-item" data-priority="<?php echo esc_attr($priority); ?>">
                                        <label class="menu-label">
                                            <input type="checkbox" class="menu-checkbox" name="selected_menus[]" value="<?php echo esc_attr($menu_slug); ?>" <?php checked($is_menu_selected); ?>>
                                            <span class="menu-title"><?php echo esc_html($menu_title); ?></span>
                                        </label>
                                        <?php if (!empty($submenu[$menu_slug])): ?>
                                            <ul class="submenu-list">
                                                <?php
                                                // Sort submenu by key as well to maintain order
                                                $sorted_submenu = $submenu[$menu_slug];
                                                ksort($sorted_submenu);
                                                foreach ($sorted_submenu as $sub_priority => $sub_item):
                                                    ?>
                                                    <?php
                                                    $sub_slug = $sub_item[2];
                                                    $sub_title = $this->clean_menu_title($sub_item[0]);
                                                    $is_submenu_selected = in_array($sub_slug, $saved_selections);
                                                    ?>
                                                    <li class="submenu-item" data-priority="<?php echo esc_attr($sub_priority); ?>">
                                                        <label class="submenu-label">
                                                            <input type="checkbox" class="submenu-checkbox" name="selected_menus[]" value="<?php echo esc_attr($sub_slug); ?>" <?php checked($is_submenu_selected); ?>>
                                                            <span class="submenu-title"><?php echo esc_html($sub_title); ?></span>
                                                        </label>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <style>
            /* General Wrap Styling */
            .menu-selector-wrap {
                max-width: 1200px;
                margin: 20px auto;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }

            .menu-selector-wrap h1 {
                color: #2c3e50;
                font-size: 2em;
                margin-bottom: 10px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }

            .menu-selector-wrap .description {
                font-size: 1.1em;
                color: #7f8c8d;
                margin-bottom: 25px;
            }

            /* Button Controls */
            .button-controls {
                display: flex;
                justify-content: flex-start;
                align-items: center;
                margin-bottom: 30px;
                gap: 10px;
            }

            .button-controls .button {
                padding: 10px 20px;
                border-radius: 5px;
                font-size: 1em;
                transition: background-color 0.3s ease, transform 0.2s ease;
            }

            .button-controls .button-primary {
                background-color: #0073aa;
                border-color: #0073aa;
                color: #fff;
            }

            .button-controls .button-primary:hover {
                background-color: #005177;
                border-color: #005177;
                transform: translateY(-1px);
            }

            .button-controls .button-secondary {
                background-color: #f3f4f6;
                border-color: #ccc;
                color: #32373c;
            }

            .button-controls .button-secondary:hover {
                background-color: #e0e0e0;
                border-color: #bbb;
                transform: translateY(-1px);
            }

            .button-controls .right-aligned {
                margin-left: auto;
            }

            /* Menu Columns Layout */
            .menu-columns-container {
                display: flex;
                flex-wrap: wrap;
                gap: 40px;
                margin-top: 20px;
            }

            .menu-column {
                flex: 1;
                min-width: 280px;
                background: #f9f9f9;
                padding: 20px;
                border-radius: 6px;
                border: 1px solid #eee;
            }

            /* Menu and Submenu Lists */
            .menu-list, .submenu-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .menu-item {
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 1px dashed #eee;
            }

            .menu-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .menu-label {
                display: flex;
                align-items: center;
                cursor: pointer;
                font-weight: bold;
                color: #333;
                font-size: 1.1em;
            }

            .menu-label input[type="checkbox"] {
                margin-right: 10px;
                transform: scale(1.2);
            }

            .submenu-list {
                margin-top: 10px;
                padding-left: 30px;
            }

            .submenu-item {
                margin-top: 8px;
            }

            .submenu-label {
                display: flex;
                align-items: center;
                cursor: pointer;
                color: #555;
                font-size: 1em;
            }

            .submenu-label input[type="checkbox"] {
                margin-right: 10px;
                transform: scale(1.1);
            }

            /* Responsive Adjustments */
            @media (max-width: 900px) {
                .menu-columns-container {
                    flex-direction: column;
                    gap: 20px;
                }
                .menu-column {
                    min-width: unset;
                    width: 100%;
                }
            }

            @media (max-width: 600px) {
                .menu-selector-wrap {
                    padding: 20px;
                    margin: 10px;
                }
                .button-controls {
                    flex-direction: column;
                    align-items: stretch;
                }
                .button-controls .button {
                    width: 100%;
                    margin-bottom: 10px;
                }
                .button-controls .right-aligned {
                    margin-left: 0;
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const selectAllBtn = document.getElementById('select-all');
                const deselectAllBtn = document.getElementById('deselect-all');
                const menuForm = document.getElementById('menu-selector-form');
                const menuCheckboxes = document.querySelectorAll('.menu-checkbox');

                // Select all checkboxes
                selectAllBtn.addEventListener('click', function () {
                    menuForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = true);
                });

                // Deselect all checkboxes
                deselectAllBtn.addEventListener('click', function () {
                    menuForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
                });

                // Add event listener for parent menu checkboxes
                menuCheckboxes.forEach(menuCheckbox => {
                    menuCheckbox.addEventListener('change', function () {
                        // Find the closest parent <li> (menu-item)
                        const menuItem = this.closest('.menu-item');
                        if (menuItem) {
                            // Find the submenu-list within this menu-item
                            const submenuList = menuItem.querySelector('.submenu-list');
                            if (submenuList) {
                                // Get all submenu checkboxes within this submenu-list
                                const submenuCheckboxes = submenuList.querySelectorAll('.submenu-checkbox');
                                // Set their checked state to match the parent menu checkbox
                                submenuCheckboxes.forEach(subCheckbox => {
                                    subCheckbox.checked = this.checked;
                                });
                            }
                        }
                    });
                });
            });
        </script>
        <?php
    }
}

new MenuSelectorHandler();