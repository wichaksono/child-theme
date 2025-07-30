<?php

namespace NeonWebId\DevTools\Modules;

use NeonWebId\DevTools\Contracts\BaseModule;

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

    }

    public function apply(): void
    {
        // TODO: Implement apply() method.
    }
}