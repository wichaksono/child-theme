<?php

use NeonWebId\DevTools\Modules\Brand;
use NeonWebId\DevTools\Utils\Panel;

return new class extends Panel {

    protected function onConstruct(): void
    {

    }

    protected function register(): array
    {
        return [
            Brand::class,
        ];
    }
};

