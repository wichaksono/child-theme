<?php
/**
 * View for Hide WP Login Module settings.
 *
 * @var NeonWebId\DevTools\Utils\Field $field The field helper class.
 * @var array $options The current options for this module.
 * @var string $login_slug The current login slug.
 *
 * @package NeonWebId\DevTools
 */

$is_enabled = !empty($options['enable_feature']);
?>

<div class="grid-container">

    <!-- Column 1: Main Settings -->
    <div class="grid-column col-span-4">
        <p>Change the default WordPress login URL to enhance security against brute-force attacks.</p>
        <br>

        <?php if ($is_enabled) : ?>
            <div class="notice notice-info inline">
                <p>
                    <strong>Current Login URL:</strong>
                    <a href="<?php echo esc_url(home_url($login_slug)); ?>" target="_blank">
                        <?php echo esc_url(home_url($login_slug)); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <div>
            <?php
            $field->switcher('enable_feature', 'Enable Hide Login', [
                'description' => '<strong>Warning:</strong> Remember your new login URL before enabling this feature!'
            ]);

            $field->text('login_slug', 'New Login Slug', [
                'description' => 'Enter a custom slug for the login URL (e.g., "secure-login", "my-admin"). Cannot be "admin", "login", or "wp-admin".',
                'placeholder' => 'login'
            ]);

            $field->select('redirect_type', 'Redirect Old URLs To', [
                    '404'    => '404 Not Found Page',
                    'home'   => 'Homepage',
                    'custom' => 'A simple "Not Found" message'
                ],
                ['description' => 'Choose where to send users who try to access the old `wp-login.php` or `wp-admin` URLs.']
            );
            ?>
        </div>
    </div>

    <!-- Column 2: Important Information -->
    <div class="grid-column col-span-4">
        <div class="card" style="border-left: 3px solid #d63638; padding: 1rem; background: #fff;">
            <h3 style="margin-top: 0; color: #d63638;">Important Information</h3>
            <ul style="list-style: disc; padding-left: 20px;">
                <li><strong>Bookmark:</strong> Always bookmark your new login URL after saving.</li>
                <li><strong>Forgot URL?</strong> If you forget the URL, you must disable this module by renaming the plugin folder via FTP or your hosting file manager.</li>
                <li><strong>Security:</strong> This feature hides `wp-login.php` and `wp-admin` from public access, making it harder for bots to find your login page.</li>
            </ul>
        </div>
    </div>

</div><!-- .grid-container -->