<?php

namespace NeonWebId\DevTools\Modules\Utilities;

use NeonWebId\DevTools\Contracts\BaseModule;

use function print_r;
use function var_dump;

final class Utilities extends BaseModule
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
        add_action('init', [$this, 'runUtilitiesHandler']);
    }

    public function runUtilitiesHandler(): void
    {
        $utilites = $this->option->get('utilities', []);
        if (empty($utilites)) {
            return;
        }

        $utilitiesHandler = new UtilitiesHandler($utilites);
        $utilitiesHandler->init();
    }
}