<?php
/**
 * This file is part of the NeonWebId DevTools package.
 *
 * It provides the implementation for the Utilities module,
 * which includes various utility functions
 *
 * @var NeonWebId\DevTools\Utils\Field $field
 *
 * @package NeonWebId\DevTools\Modules\Utilities
 */
?>
<div class="grid-container">

    <!-- Column 1: Core Feature Toggles -->
    <div class="grid-column col-span-3">
        <h3>Core Features</h3>
        <div>
            <?php
            $field->switcher('disable_comments', 'Disable Comments', [
                'description' => 'Completely disables comments site-wide, hiding existing ones and closing new submissions.'
            ]);
            $field->switcher('disable_updates', 'Disable All Updates', [
                'description' => 'Disables all plugin, theme, and core update checks. Use with caution.'
            ]);
            $field->switcher('disable_wp_cron', 'Disable WP Cron', [
                'description' => 'Disables the default cron. A server-side cron job is recommended as a replacement.'
            ]);

            // The main toggle for limiting post revisions.
            $field->switcher('limit_post_revisions_enabled', 'Limit Post Revisions', [
                'description' => 'Enable this to control the number of revisions stored for each post.'
            ]);

            // The number field, which ONLY appears if the switcher above is ON.
            $field->number('post_revisions_count', 'Number of Revisions to Keep', [
                'description'  => 'Enter the maximum number of revisions. For example: 3.',
                'placeholder'  => '3',
                'min'          => 0,
                'dependencies' => [
                    [
                        'field'     => 'limit_post_revisions_enabled', // The ID of the switcher
                        'condition' => '==',
                        'value'     => ['1'] // The value when checkbox is checked
                    ]
                ]
            ]);

            ?>
        </div>
    </div>

    <!-- Column 2: Security -->
    <div class="grid-column col-span-3">
        <h3>Security</h3>
        <div>
            <?php
            $field->switcher('disable_file_editor', 'Disable File Editor', [
                'description' => 'Removes the Theme/Plugin File Editor from the admin menu for better security.'
            ]);
            $field->switcher('disable_xmlrpc', 'Disable XML-RPC', [
                'description' => 'Disables the XML-RPC protocol, a common target for brute-force attacks.'
            ]);
            $field->switcher('disable_rest_api', 'Disable REST API', [
                'description' => 'Disables the REST API for non-logged-in users to prevent public data exposure.'
            ]);
            ?>
        </div>
    </div>

    <!-- Column 3: Header Cleanup -->
    <div class="grid-column col-span-3">
        <h3>Header Cleanup</h3>
        <div>
            <?php
            $field->switcher('disable_emoji', 'Disable Emojis', [
                'description' => 'Removes the extra JavaScript file used to render emojis, slightly improving performance.'
            ]);
            $field->switcher('remove_pingback', 'Remove Pingback Tag', [
                'description' => 'Removes the pingback link from the site\'s header.'
            ]);
            $field->switcher('remove_rsd_link', 'Remove RSD Link', [
                'description' => 'Removes the Really Simple Discovery (RSD) link, used by some blog clients.'
            ]);
            ?>
        </div>
    </div>

    <!-- Column 4: Meta Tag Removal -->
    <div class="grid-column col-span-3">
        <h3>Meta Tags</h3>
        <div>
            <?php
            $field->switcher('remove_wlwmanifest', 'Remove wlwmanifest Link', [
                'description' => 'Removes the Windows Live Writer manifest link from the site\'s header.'
            ]);
            $field->switcher('remove_wp_version', 'Remove WordPress Version', [
                'description' => 'Hides the WordPress version number from the site\'s header for security.'
            ]);
            $field->switcher('remove_wp_generator', 'Remove WordPress Generator', [
                'description' => 'Removes the "generator" meta tag which also shows the WP version.'
            ]);
            $field->switcher('remove_shortlink', 'Remove WordPress Shortlink', [
                'description' => 'Removes the shortlink meta tag from single posts and pages.'
            ]);
            ?>
        </div>
    </div>

</div><!-- .grid-container -->