<?php
/**
 * View for Menu Hider Module settings.
 *
 * @var NeonWebId\DevTools\Utils\Field $field The field utility class.
 * @var array $all_menus The complete list of menu items.
 * @var array $options The currently saved options (hidden menu slugs).
 *
 * @package NeonWebId\DevTools
 */
$menu_chunks = array_chunk($all_menus, ceil(count($all_menus) / 3), true);
?>

<style>
    .menu-hider-controls { display: flex; gap: 10px; margin-bottom: 1.5rem; align-items: center; }
    .menu-hider-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
    @media (max-width: 900px) { .menu-hider-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .menu-hider-grid { grid-template-columns: 1fr; } }
    .menu-column { background: #fff; border: 1px solid #dcdcde; border-radius: 4px; padding: 1rem; }
    .menu-column ul { list-style: none; margin: 0; padding: 0; }
    .menu-column li.menu-item { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed #e0e0e0; }
    .menu-column li.menu-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .menu-column .parent-menu .field-label { font-weight: 600; font-size: 14px; }
    .menu-column .submenu-list { margin-top: 0.75rem; padding-left: 1.5rem; }
</style>

<p>Pilih item menu dan submenu admin yang ingin Anda sembunyikan. Menu yang dicentang akan disembunyikan.</p>

<div class="menu-hider-controls">
    <button type="button" id="menu-hider-select-all" class="button button-secondary">Pilih Semua</button>
    <button type="button" id="menu-hider-deselect-all" class="button button-secondary">Hapus Semua</button>
</div>

<input type="hidden" name="<?php echo $field->get_prefixed_name(''); ?>" value="1" />

<div id="menu-hider-container" class="menu-hider-grid">
    <?php foreach ($menu_chunks as $chunk) : ?>
        <div class="menu-column">
            <ul>
                <?php foreach ($chunk as $menu) :; ?>
                    <li class="menu-item">
                        <div class="parent-menu">
                            <?php $field->checkbox( // PERBAIKAN: Gunakan $field, bukan $module->field
                                $menu['slug'],
                                $menu['title'],
                                [
                                    'value' => $menu['slug'],
                                    'checked' => in_array($menu['slug'], $options, true)
                                ]
                            ); ?>
                        </div>
                        <?php if (!empty($menu['sub'])) : ?>
                            <ul class="submenu-list">
                                <?php foreach ($menu['sub'] as $sub_menu) : ?>
                                    <li>
                                        <?php $field->checkbox( // PERBAIKAN: Gunakan $field, bukan $module->field
                                            $sub_menu['slug'],
                                            $sub_menu['title'],
                                            [
                                                'value' => $sub_menu['slug'],
                                                'checked' => in_array($sub_menu['slug'], $options, true)
                                            ]
                                        ); ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('menu-hider-container');
        if (!container) return;

        const selectAllBtn = document.getElementById('menu-hider-select-all');
        const deselectAllBtn = document.getElementById('menu-hider-deselect-all');
        const allCheckboxes = container.querySelectorAll('input[type="checkbox"]');

        selectAllBtn.addEventListener('click', () => {
            allCheckboxes.forEach(cb => cb.checked = true);
        });

        deselectAllBtn.addEventListener('click', () => {
            allCheckboxes.forEach(cb => cb.checked = false);
        });

        // Parent-child checking logic
        container.addEventListener('change', function(e) {
            if (e.target.type !== 'checkbox') return;

            const parentLi = e.target.closest('li.menu-item');
            if (!parentLi) return;

            // If a parent menu is clicked, check/uncheck all its children
            const isParentCheckbox = e.target.closest('.parent-menu');
            if (isParentCheckbox) {
                const childCheckboxes = parentLi.querySelectorAll('.submenu-list input[type="checkbox"]');
                childCheckboxes.forEach(child => child.checked = e.target.checked);
            }
        });
    });
</script>