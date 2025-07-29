<?php

namespace NeonWebId\DevTools\Modules;

use NeonWebId\DevTools\Contracts\Base;

final class Brand extends Base
{

    public function id(): string
    {
        return 'brand';
    }

    public function title(): string
    {
        return __('Brand', 'dev-tools');
    }

    public function name(): string
    {
        return 'Brand';
    }

    public function content(): void
    {
        echo $this->fieldName('logo');
    }
}