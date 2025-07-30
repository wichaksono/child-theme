<?php
/**
 * View for Brand Module settings.
 *
 * @var NeonWebId\DevTools\Utils\Field $field The field helper class.
 *
 * @package NeonWebId\DevTools
 */
?>

<div class="grid-container">

    <!-- Column 1: Admin Bar -->
    <div class="grid-column col-span-4">
        <h3>Admin Bar</h3>
        <div>
            <?php
            $field->media('admin_logo_image_url', 'Admin Bar Logo', [
                'description' => 'Upload a logo to replace the default WordPress icon in the admin bar. Recommended height: 20px.'
            ]);
            $field->text('admin_brand_text', 'Admin Bar Brand Text', [
                'description' => 'The text displayed next to the logo. Defaults to the site title.',
                'placeholder' => get_bloginfo('name')
            ]);
            $field->text('admin_brand_link_url', 'Admin Bar Brand Link', [
                'description' => 'The URL the logo and text will link to. Defaults to the site homepage.',
                'placeholder' => home_url()
            ]);
            ?>
        </div>
    </div>

    <!-- Column 2: Admin Footer -->
    <div class="grid-column col-span-4">
        <h3>Admin Footer</h3>
        <div>
            <?php
            $field->text('admin_footer_text', 'Footer Text', [
                'description' => 'Replaces the "Thank you for creating with WordPress" text.'
            ]);
            $field->switcher('remove_wp_version', 'Remove WordPress Version', [
                'description' => 'Removes the WordPress version number from the bottom right of the admin footer.'
            ]);
            ?>
        </div>
    </div>

    <!-- Column 3: Custom Dashboard -->
    <div class="grid-column col-span-4">
        <h3>Custom Dashboard</h3>
        <div>
            <?php
            $field->text('custom_dashboard_slug', 'Custom Dashboard Slug', [
                'description' => 'Enter a unique slug (e.g., "company-dashboard") to enable a custom dashboard and redirect the default one. Leave blank to disable.'
            ]);
            ?>
        </div>
        <p class="description" style="margin-top:1rem;"><strong>Note:</strong> To customize the content of the dashboard page, you will need to edit the `custom_dashboard_page_content` method in `BrandHandler.php`.</p>
    </div>

</div><!-- .grid-container -->