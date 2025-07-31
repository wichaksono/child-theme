<?php

namespace NeonWebId\DevTools\Modules\TelegramNotify;

use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Utils\View;
use NeonWebId\DevTools\Utils\DevOption;

use function print_r;

final class TelegramNotify extends BaseModule
{
    public function id(): string
    {
        return 'telegram_notify';
    }

    public function title(): string
    {
        return 'Telegram Notifier';
    }

    public function name(): string
    {
        return 'Telegram';
    }

    public function content(): void
    {
        // Menggunakan view terpisah untuk menjaga kebersihan kode
        $this->view->render('telegram-notify/telegram-notify', [
            'field'   => $this->field,
        ]);
    }

    public function apply(): void
    {
        $options = $this->option->get($this->id(), []);

        // Hanya jalankan handler jika Bot Token dan Chat ID sudah diisi.
        if (!empty($options['bot_token']) && !empty($options['chat_id'])) {
            $handler = new TelegramNotifyHandler($options);
            $handler->init();
        }
    }
}