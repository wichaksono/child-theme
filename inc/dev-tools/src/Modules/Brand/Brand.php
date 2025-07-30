<?php

namespace NeonWebId\DevTools\Modules\Brand;

use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Utils\DevOption;
use NeonWebId\DevTools\Utils\View;
use function __;

final class Brand extends BaseModule
{
    public function id(): string
    {
        return 'brand';
    }

    public function title(): string
    {
        return __('Brand Settings', 'dev-tools');
    }

    public function name(): string
    {
        return 'Brand';
    }

    public function content(): void
    {
        $this->view->render('brand/brand', [
            'field' => $this->field,
        ]);
    }

    public function apply(): void
    {
        $options = $this->option->get($this->id(), []);

        // Jalankan handler hanya jika ada setidaknya satu opsi yang diatur.
        if (!empty(array_filter($options))) {
            new BrandHandler($options);
        }
    }
}