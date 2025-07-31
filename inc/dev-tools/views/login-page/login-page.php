<?php
/**
 * View for Login Page Module settings.
 *
 * @var NeonWebId\DevTools\Utils\Field $field The field helper class.
 *
 * @package NeonWebId\DevTools
 */
?>

<div class="grid-container">

    <!-- Column 1: General & Logo -->
    <div class="grid-column col-span-4">
        <h3>General</h3>
        <?php
        $field->switcher('enable_feature', 'Enable Login Page Customization', [
            'description' => 'Activate to apply all settings below to the WordPress login page.'
        ]);
        ?>

        <h3 style="margin-top: 2rem;">Logo Settings</h3>
        <?php
        $field->media('login_logo_image_url', 'Custom Logo', [
            'description' => 'Upload a logo to replace the default WordPress logo. Recommended height: 80px.'
        ]);
        $field->text('login_logo_link_url', 'Logo Link URL', [
            'description' => 'The URL the logo will link to. Defaults to your site\'s homepage.',
            'placeholder' => home_url()
        ]);
        $field->text('login_logo_text', 'Logo Alt Text', [
            'description' => 'Alternative text for the logo, important for accessibility. Defaults to your site\'s name.',
            'placeholder' => get_bloginfo('name')
        ]);
        ?>
    </div>

    <!-- Column 2: Color Settings -->
    <div class="grid-column col-span-4">
        <h3>Color Settings</h3>
        <?php
        $field->color('form_background_color', 'Form Background Color', [
            'default' => '#FFFFFF'
        ]);
        $field->color('text_link_color', 'Text & Link Color', [
            'description' => 'For links like "Lost your password?" and "Back to [Site]".',
            'default' => '#0073AA'
        ]);
        $field->color('button_color', 'Button Color', [
            'default' => '#0073AA'
        ]);
        $field->color('button_hover_color', 'Button Hover Color', [
            'default' => '#005177'
        ]);
        ?>
    </div>

    <!-- Column 3: Footer -->
    <div class="grid-column col-span-4">
        <h3>Footer Settings</h3>
        <?php
        $field->text('login_footer_text', 'Footer Text', [
            'description' => 'Add a custom line of text below the login form.',
            'placeholder' => 'Powered by My Company'
        ]);
        $field->text('login_footer_url', 'Footer Link URL', [
            'description' => 'Make the footer text a clickable link. Leave blank for no link.',
            'placeholder' => home_url()
        ]);
        ?>
    </div>

</div><!-- .grid-container -->