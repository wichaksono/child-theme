<?php

use NeonWebId\DevTools\Contracts\BaseModule;
use NeonWebId\DevTools\Modules\Brand\Brand;
use NeonWebId\DevTools\Modules\TelegramNotify\TelegramNotify;
use NeonWebId\DevTools\Modules\Utilities\Utilities;
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
            Utilities::class,
            TelegramNotify::class,
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

        // Uncomment the following lines to enable theme updates
        //$this->updater->setUpdateServer('https://wp-central.neon.web.id/plugin/')
        //    ->setHeaders([
        //        'Authorization' => 'Bearer ' . get_option('dev_tools_api_key', ''),
        //        'X-Theme-Slug' => get_stylesheet(),
        //    ])
        //    ->sslVerify(true)
        //    ->setTimeout(30)
        //    ->setParams([
        //        'slug' => get_stylesheet(),
        //    ])
        //    ->setThemeData(wp_get_theme(get_stylesheet()));

    }
};