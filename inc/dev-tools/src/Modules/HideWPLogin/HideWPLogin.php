<?php

namespace NeonWebId\DevTools\Modules\HideWPLogin;

use NeonWebId\DevTools\Contracts\BaseModule;

use function __;

final class HideWPLogin extends BaseModule
{
    public function id(): string
    {
        return 'hide_wp_login';
    }

    public function title(): string
    {
        return __('Hide WP Login', 'dev-tools');
    }

    public function name(): string
    {
        return 'Hide Login';
    }

    public function content(): void
    {
        $this->view->render('hide-wp-login/hide-wp-login', [
            'field'      => $this->field,
            'login_slug' => $this->option->get($this->id(), [])['login_slug'] ?? 'login'
        ]);
    }

    public function apply(): void
    {
        $options = $this->option->get($this->id(), []);

        // Only run the handler if the feature is enabled
        if ( ! empty($options['enable_feature'])) {
            new HideWPLoginHandler($options);
        }
    }
}