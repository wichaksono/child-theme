<?php

namespace NeonWebId\DevTools\Modules\TelegramNotify;

use DateTime;
use Exception;
use function add_action;
use function checked;
use function current_time;
use function get_option;
use function get_permalink;
use function get_post_type_object;
use function get_the_author_meta;
use function get_transient;
use function get_userdata;
use function in_array;
use function is_array;
use function sanitize_key;
use function set_transient;
use function wp_next_scheduled;
use function wp_remote_post;
use function wp_schedule_event;
use const DAY_IN_SECONDS;

/**
 * Class TelegramNotifyHandler
 *
 * Handles all logic for sending Telegram notifications based on saved options.
 */
final class TelegramNotifyHandler
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Initializes the handler by adding all necessary WordPress hooks.
     */
    public function init(): void
    {
        // Notification Hooks
        if ($this->isEnabled('user_login')) {
            add_action('wp_login', [$this, 'userLoginNotification'], 10, 2);
        }
        if ($this->isEnabled('user_activity')) {
            add_action('transition_post_status', [$this, 'postPublishedNotification'], 10, 3);
            add_action('user_register', [$this, 'userRegisterNotification']);
            add_action('profile_update', [$this, 'profileUpdateNotification'], 10, 2);
        }
        if ($this->isEnabled('updates')) {
            add_action('upgrader_process_complete', [$this, 'coreUpdateNotification'], 10, 2);
        }

        // Cron job for reminders
        if (!wp_next_scheduled('tn_check_expirations')) {
            wp_schedule_event(time(), 'daily', 'tn_check_expirations');
        }
        add_action('tn_check_expirations', [$this, 'checkExpirationDates']);
    }

    private function isEnabled(string $key): bool
    {
        return !empty($this->options[$key]);
    }

    private function sendTelegramMessage(string $message): void
    {
        $bot_token = $this->options['bot_token'] ?? '';
        $chat_id = $this->options['chat_id'] ?? '';
        if (empty($bot_token) || empty($chat_id)) {
            return;
        }

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        wp_remote_post($url, ['body' => ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML']]);
    }

    public function userLoginNotification($user_login, $user): void
    {
        $message = "✅ <b>User Login</b>\nUsername: {$user_login}\nRole: " . implode(', ', $user->roles) . "\nWaktu: " . current_time('mysql');
        $this->sendTelegramMessage($message);
    }

    public function postPublishedNotification($new_status, $old_status, $post): void
    {
        if ('publish' === $new_status && 'publish' !== $old_status && in_array($post->post_type, ['post', 'page'])) {
            $post_type_label = get_post_type_object($post->post_type)->labels->singular_name;
            $message = "📝 <b>Konten Baru: {$post_type_label}</b>\nJudul: {$post->post_title}\nOleh: " . get_the_author_meta('display_name', $post->post_author) . "\nLink: " . get_permalink($post->ID);
            $this->sendTelegramMessage($message);
        }
    }

    public function userRegisterNotification($user_id): void
    {
        $user = get_userdata($user_id);
        $message = "👤 <b>User Baru Terdaftar</b>\nUsername: {$user->user_login}\nEmail: {$user->user_email}";
        $this->sendTelegramMessage($message);
    }

    public function profileUpdateNotification($user_id, $old_user_data): void
    {
        $user = get_userdata($user_id);
        $message = "✏️ <b>Profil User Diedit</b>\nUsername: {$user->user_login}";
        $this->sendTelegramMessage($message);
    }

    public function coreUpdateNotification($upgrader_object, $options): void
    {
        if ($options['action'] == 'update' && in_array($options['type'], ['plugin', 'theme'])) {
            $type = $options['type'] == 'plugin' ? 'Plugin' : 'Tema';
            $items = implode(', ', array_merge($options['plugins'] ?? [], $options['themes'] ?? []));
            $message = "🔄 <b>Update {$type}</b>\nItem yang diupdate: {$items}";
            $this->sendTelegramMessage($message);
        }
    }

    public function checkExpirationDates(): void
    {
        if (empty($this->options['reminders']) || !is_array($this->options['reminders'])) {
            return;
        }

        $today = new DateTime();
        $today->setTime(0, 0, 0);

        foreach ($this->options['reminders'] as $reminder) {
            if (empty($reminder['name']) || empty($reminder['date'])) {
                continue;
            }

            try {
                $expire_date = new DateTime($reminder['date']);
                $expire_date->setTime(0, 0, 0);
                $reminder_days = absint($reminder['days'] ?? 7);
                $reminder_date = (new DateTime($reminder['date']))->modify("-{$reminder_days} days")->setTime(0, 0, 0);

                if ($today >= $reminder_date && $today <= $expire_date) {
                    $transient_key = 'tn_reminder_sent_' . sanitize_key($reminder['name']) . '_' . $expire_date->format('Ymd');
                    if (get_transient($transient_key)) {
                        continue;
                    }

                    $interval = $today->diff($expire_date);
                    $days_left = (int) $interval->format('%r%a');
                    $message = "🔔 <b>Pengingat: {$reminder['name']}</b>\n";

                    if ($days_left < 0) {
                        $message .= "Telah kedaluwarsa <b>" . abs($days_left) . " hari</b> yang lalu.";
                    } elseif ($days_left == 0) {
                        $message .= "Akan kedaluwarsa <b>HARI INI</b>.";
                    } else {
                        $message .= "Akan kedaluwarsa dalam <b>{$days_left} hari</b> lagi.";
                    }
                    $message .= "\nTanggal Kedaluwarsa: {$expire_date->format('d F Y')}. Harap segera ditindaklanjuti.";

                    $this->sendTelegramMessage($message);
                    set_transient($transient_key, true, DAY_IN_SECONDS);
                }
            } catch (Exception $e) {
                // Abaikan jika tanggal tidak valid
                continue;
            }
        }
    }
}