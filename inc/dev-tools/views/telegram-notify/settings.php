<?php
/**
 * View for Telegram Notifier Module settings.
 *
 * @var NeonWebId\DevTools\Utils\Field $field The field helper class.
 *
 * @package NeonWebId\DevTools
 */
?>

<div class="grid-container">

    <!-- Column 1: General Settings -->
    <div class="grid-column col-span-4">
        <h3>General Settings</h3>
        <div>
            <?php
            $field->text('bot_token', 'Bot Token', [
                'description' => 'Enter your Telegram Bot Token.',
            ]);
            $field->text('chat_id', 'Chat ID', [
                'description' => 'Enter the Chat ID (user or group) to receive notifications.',
            ]);
            ?>
        </div>
    </div>
    <!-- Column 2: Notification Settings -->
    <div class="grid-column col-span-4">
        <h3>Notification Settings</h3>
        <div>
            <?php
            $field->switcher('user_login', 'User Login Notification', [
                'description' => 'Enable notifications when a user successfully logs in.',
            ]);
            $field->switcher('user_activity', 'User Activity Notification', [
                'description' => 'Notifications for new posts/pages, new users, and profile edits.',
            ]);
            $field->switcher('updates', 'Update Notification', [
                'description' => 'Notifications for theme & plugin updates.',
            ]);
            ?>
        </div>
    </div>

    <!-- Column 3: Expiration Reminders -->
    <div class="grid-column col-span-4">
        <h3>Expiration Reminders</h3>
        <p class="description">Add reminders for multiple items at once, such as domains, hosting, or plugin licenses. Reminders will be sent daily once the warning period begins.</p>
        <br>
        <div>
            <?php
            $field->repeater('reminders', 'Reminder List', [
                'fields' => [
                    [
                        'id'    => 'name',
                        'label' => 'Reminder Name',
                        'type'  => 'text',
                        'placeholder' => 'Example: Hosting A'
                    ],
                    [
                        'id'    => 'date',
                        'label' => 'Expiration Date',
                        'type'  => 'date',
                    ],
                    [
                        'id'    => 'days',
                        'label' => 'Remind Since (Days)',
                        'type'  => 'number',
                        'placeholder' => '7',
                        'default' => 7
                    ],
                ]
            ]);
            ?>
        </div>
    </div>

</div><!-- .grid-container -->