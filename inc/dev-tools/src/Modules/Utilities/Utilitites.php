<?php

namespace NeonWebId\DevTools\Modules\Utilities;

use NeonWebId\DevTools\Contracts\BaseModule;

final class Utilitites extends BaseModule
{
    public function id(): string
    {
        return 'utilities';
    }

    public function title(): string
    {
        return __('Utilities', 'dev-tools');
    }

    public function name(): string
    {
        return __('Utilities', 'dev-tools');
    }

    public function content(): void
    {
        $this->view->render('utilities/utilities', [
            'field' => $this->field
        ]);
    }

    public function apply(): void
    {
        // TODO: Implement apply() method.
    }
}