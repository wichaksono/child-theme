<?php

namespace NeonWebId\DevTools\Modules\LoginPage;

use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Utils\DevOption;
use NeonWebId\DevTools\Utils\View;
use function __;

final class LoginPage extends BaseModule
{
    public function id(): string
    {
        return 'login_page';
    }

    public function title(): string
    {
        return __('Login Page', 'dev-tools');
    }

    public function name(): string
    {
        return 'Login Page';
    }

    public function content(): void
    {
        $this->view->render('login-page/login-page', [
            'field' => $this->field,
        ]);
    }

    public function apply(): void
    {
        $options = $this->option->get($this->id(), []);

        if (!empty($options['enable_feature'])) {
            new LoginPageHandler($options);
        }
    }
}