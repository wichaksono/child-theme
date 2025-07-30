<?php
/**
 * Class Wichaksono_Telegram_Notifier
 *
 * Mengelola semua notifikasi Telegram untuk aktivitas WordPress.
 * Dapat diintegrasikan ke dalam functions.php child theme.
 *
 * @author GitHub Copilot
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Wichaksono_Telegram_Notifier {

    private static $instance;
    private $options;

    /**
     * Singleton instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->options = get_option('tn_settings');
        $this->add_hooks();
    }

    /**
     * Menambahkan semua hook WordPress yang diperlukan.
     */
    private function add_hooks() {
        // Hooks untuk halaman pengaturan
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_footer', [$this, 'add_reminder_script']);

        // Hooks untuk notifikasi
        add_action('wp_login', [$this, 'user_login_notification'], 10, 2);
        add_action('transition_post_status', [$this, 'post_published_notification'], 10, 3);
        add_action('user_register', [$this, 'user_register_notification']);
        add_action('profile_update', [$this, 'profile_update_notification'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'core_update_notification'], 10, 2);

        // Cron job untuk pengingat
        if (!wp_next_scheduled('tn_check_expirations')) {
            wp_schedule_event(time(), 'daily', 'tn_check_expirations');
        }
        add_action('tn_check_expirations', [$this, 'check_expiration_dates']);
    }

    //======================================================================
    // PENGATURAN HALAMAN ADMIN
    //======================================================================

    public function add_admin_menu() {
        add_menu_page(
            'Telegram Notifier', 'Telegram Notifier', 'manage_options',
            'telegram_notifier', [$this, 'settings_page_html'], 'dashicons-telegram'
        );
    }

    public function register_settings() {
        register_setting('tn_settings_group', 'tn_settings', [$this, 'sanitize_settings']);

        add_settings_section('tn_general_section', 'Pengaturan Umum', null, 'telegram_notifier');
        add_settings_field('tn_bot_token', 'Bot Token', [$this, 'render_text_field'], 'telegram_notifier', 'tn_general_section', ['id' => 'bot_token']);
        add_settings_field('tn_chat_id', 'Chat ID', [$this, 'render_text_field'], 'telegram_notifier', 'tn_general_section', ['id' => 'chat_id']);

        add_settings_section('tn_notifications_section', 'Pengaturan Notifikasi', null, 'telegram_notifier');
        add_settings_field('tn_user_login', 'Notifikasi User Login', [$this, 'render_checkbox_field'], 'telegram_notifier', 'tn_notifications_section', ['id' => 'user_login', 'label' => 'Aktifkan notifikasi saat user login']);
        add_settings_field('tn_user_activity', 'Notifikasi Aktivitas User', [$this, 'render_checkbox_field'], 'telegram_notifier', 'tn_notifications_section', ['id' => 'user_activity', 'label' => 'Aktifkan notifikasi untuk post/page baru, user baru, dan edit profil']);
        add_settings_field('tn_updates', 'Notifikasi Update', [$this, 'render_checkbox_field'], 'telegram_notifier', 'tn_notifications_section', ['id' => 'updates', 'label' => 'Aktifkan notifikasi untuk update tema & plugin']);

        add_settings_section('tn_reminder_section', 'Pengaturan Pengingat Kedaluwarsa', null, 'telegram_notifier');
        add_settings_field('tn_reminders', 'Daftar Pengingat', [$this, 'render_reminders_field'], 'telegram_notifier', 'tn_reminder_section');
    }

    public function settings_page_html() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('tn_settings_group');
                do_settings_sections('telegram_notifier');
                submit_button('Simpan Pengaturan');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_text_field($args) {
        printf('<input type="text" name="tn_settings[%s]" value="%s" class="regular-text">', esc_attr($args['id']), esc_attr($this->options[$args['id']] ?? ''));
    }

    public function render_checkbox_field($args) {
        printf('<label><input type="checkbox" name="tn_settings[%s]" value="1" %s> %s</label>', esc_attr($args['id']), checked($this->options[$args['id']] ?? 0, 1, false), esc_html($args['label']));
    }

    public function render_reminders_field() {
        $reminders = $this->options['reminders'] ?? [['name' => '', 'date' => '', 'days' => '7']];
        ?>
        <div id="reminders-wrapper">
            <?php foreach ($reminders as $index => $reminder) : ?>
                <div class="reminder-item" style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="text" name="tn_settings[reminders][<?php echo $index; ?>][name]" value="<?php echo esc_attr($reminder['name']); ?>" placeholder="Nama Pengingat (cth: Hosting A)" style="width: 250px; margin-right: 10px;">
                    <input type="date" name="tn_settings[reminders][<?php echo $index; ?>][date]" value="<?php echo esc_attr($reminder['date']); ?>" style="margin-right: 10px;">
                    <input type="number" name="tn_settings[reminders][<?php echo $index; ?>][days]" value="<?php echo esc_attr($reminder['days']); ?>" placeholder="Hari" style="width: 60px; margin-right: 10px;">
                    <button type="button" class="button button-secondary remove-reminder">- Hapus</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="add-reminder">+ Tambah Pengingat</button>
        <p class="description">Tambahkan pengingat untuk beberapa item sekaligus, seperti domain, hosting, atau lisensi plugin.</p>
        <?php
    }

    public function add_reminder_script() {
        if (!is_admin() || get_current_screen()->id !== 'toplevel_page_telegram_notifier') return;
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const wrapper = document.getElementById('reminders-wrapper');
                const addButton = document.getElementById('add-reminder');

                addButton.addEventListener('click', function() {
                    const index = wrapper.getElementsByClassName('reminder-item').length;
                    const newItem = document.createElement('div');
                    newItem.className = 'reminder-item';
                    newItem.style = 'display: flex; align-items: center; margin-bottom: 10px;';
                    newItem.innerHTML = `
                        <input type="text" name="tn_settings[reminders][${index}][name]" value="" placeholder="Nama Pengingat" style="width: 250px; margin-right: 10px;">
                        <input type="date" name="tn_settings[reminders][${index}][date]" value="" style="margin-right: 10px;">
                        <input type="number" name="tn_settings[reminders][${index}][days]" value="7" placeholder="Hari" style="width: 60px; margin-right: 10px;">
                        <button type="button" class="button button-secondary remove-reminder">- Hapus</button>
                    `;
                    wrapper.appendChild(newItem);
                });

                wrapper.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('remove-reminder')) {
                        if (wrapper.getElementsByClassName('reminder-item').length > 1) {
                            e.target.closest('.reminder-item').remove();
                        } else {
                            alert('Anda tidak bisa menghapus baris terakhir.');
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function sanitize_settings($input) {
        $sanitized = [];
        $sanitized['bot_token'] = sanitize_text_field($input['bot_token'] ?? '');
        $sanitized['chat_id'] = sanitize_text_field($input['chat_id'] ?? '');
        $sanitized['user_login'] = isset($input['user_login']) ? 1 : 0;
        $sanitized['user_activity'] = isset($input['user_activity']) ? 1 : 0;
        $sanitized['updates'] = isset($input['updates']) ? 1 : 0;

        $sanitized['reminders'] = [];
        if (!empty($input['reminders']) && is_array($input['reminders'])) {
            foreach ($input['reminders'] as $reminder) {
                if (!empty($reminder['name']) && !empty($reminder['date'])) {
                    $sanitized['reminders'][] = [
                        'name' => sanitize_text_field($reminder['name']),
                        'date' => sanitize_text_field($reminder['date']),
                        'days' => absint($reminder['days']),
                    ];
                }
            }
        }
        return $sanitized;
    }

    //======================================================================
    // FUNGSI NOTIFIKASI
    //======================================================================

    private function send_telegram_message($message) {
        $bot_token = $this->options['bot_token'] ?? '';
        $chat_id = $this->options['chat_id'] ?? '';
        if (empty($bot_token) || empty($chat_id)) return;

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        wp_remote_post($url, ['body' => ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML']]);
    }

    public function user_login_notification($user_login, $user) {
        if (!empty($this->options['user_login'])) {
            $message = "✅ <b>User Login</b>\nUsername: {$user_login}\nRole: " . implode(', ', $user->roles) . "\nWaktu: " . current_time('mysql');
            $this->send_telegram_message($message);
        }
    }

    public function post_published_notification($new_status, $old_status, $post) {
        if (!empty($this->options['user_activity']) && 'publish' === $new_status && 'publish' !== $old_status && in_array($post->post_type, ['post', 'page'])) {
            $post_type_label = get_post_type_object($post->post_type)->labels->singular_name;
            $message = "📝 <b>Konten Baru: {$post_type_label}</b>\nJudul: {$post->post_title}\nOleh: " . get_the_author_meta('display_name', $post->post_author) . "\nLink: " . get_permalink($post->ID);
            $this->send_telegram_message($message);
        }
    }

    public function user_register_notification($user_id) {
        if (!empty($this->options['user_activity'])) {
            $user = get_userdata($user_id);
            $message = "👤 <b>User Baru Terdaftar</b>\nUsername: {$user->user_login}\nEmail: {$user->user_email}";
            $this->send_telegram_message($message);
        }
    }

    public function profile_update_notification($user_id, $old_user_data) {
        if (!empty($this->options['user_activity'])) {
            $user = get_userdata($user_id);
            $message = "✏️ <b>Profil User Diedit</b>\nUsername: {$user->user_login}";
            $this->send_telegram_message($message);
        }
    }

    public function core_update_notification($upgrader_object, $options) {
        if (!empty($this->options['updates']) && $options['action'] == 'update' && in_array($options['type'], ['plugin', 'theme'])) {
            $type = $options['type'] == 'plugin' ? 'Plugin' : 'Tema';
            $items = implode(', ', array_merge($options['plugins'] ?? [], $options['themes'] ?? []));
            $message = "🔄 <b>Update {$type}</b>\nItem yang diupdate: {$items}";
            $this->send_telegram_message($message);
        }
    }

    public function check_expiration_dates() {
        if (empty($this->options['reminders']) || !is_array($this->options['reminders'])) return;

        $today = new DateTime();
        $today->setTime(0, 0, 0);

        foreach ($this->options['reminders'] as $reminder) {
            if (empty($reminder['name']) || empty($reminder['date'])) continue;

            try {
                $expire_date = new DateTime($reminder['date']);
                $expire_date->setTime(0, 0, 0);
                $reminder_date = (new DateTime($reminder['date']))->modify("-{$reminder['days']} days")->setTime(0, 0, 0);

                if ($today >= $reminder_date && $today <= $expire_date) {
                    $transient_key = 'tn_reminder_sent_' . sanitize_key($reminder['name']) . '_' . $expire_date->format('Ymd');
                    if (get_transient($transient_key)) continue;

                    $interval = $today->diff($expire_date);
                    $days_left = $interval->format('%r%a');
                    $message = "🔔 <b>Pengingat: {$reminder['name']}</b>\n";

                    if ($days_left < 0) {
                        $message .= "Telah kedaluwarsa <b>" . abs($days_left) . " hari</b> yang lalu.";
                    } elseif ($days_left == 0) {
                        $message .= "Akan kedaluwarsa <b>HARI INI</b>.";
                    } else {
                        $message .= "Akan kedaluwarsa dalam <b>{$days_left} hari</b> lagi.";
                    }
                    $message .= "\nTanggal: {$expire_date->format('d-m-Y')}. Harap segera ditindaklanjuti.";

                    $this->send_telegram_message($message);
                    set_transient($transient_key, true, DAY_IN_SECONDS);
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
}