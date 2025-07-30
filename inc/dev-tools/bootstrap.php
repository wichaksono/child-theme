<?php

use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Modules\Brand;
use NeonWebId\DevTools\Utils\Panel;

/**
 * This file bootstraps the DevTools Panel.
 *
 * It returns an anonymous class that extends the main Panel,
 * allowing for project-specific configuration and module registration
 * without modifying the core Panel class itself.
 */
return new class extends Panel {

    /**
     * Register all the modules that the DevTools panel will use.
     *
     * Each entry in the array should be the fully qualified class name
     * of a class that implements the BaseModule contract.
     *
     * @return array<class-string<BaseModule>> A list of module classes to load.
     */
    protected function modules(): array
    {
        return [
            Brand::class,
        ];
    }

    /**
     * A lifecycle hook that runs after the Panel's constructor has finished.
     *
     * Use this method to add any custom initialization logic specific
     * to this implementation of the panel.
     *
     * @return void
     */
    protected function onConstruct(): void
    {
        // This space can be used for future initializations.
    }
};