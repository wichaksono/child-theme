<?php

defined('ABSPATH') || exit;

/**
 * Dev Tools Constants and Autoload
 *
 * @package NeonWebId\DevTools
 * @since 1.0.0
 */
define('DEV_TOOLS_VERSION', '1.0.0');

define('DEV_TOOLS_HASH', '1.0.0');

return new class
{
    public function __construct()
    {
        spl_autoload_register([$this, 'loadClass']);

        (require_once __DIR__ . '/bootstrap.php')->run();
    }

    public function loadClass($className): void
    {
        $baseNamespce = 'NeonWebId\\DevTools\\';
        $len          = strlen($baseNamespce);
        if (strncmp($baseNamespce, $className, $len) !== 0) {
            return;
        }
        $className = substr($className, $len);
        $file = __DIR__ . '/src/' . str_replace('\\', '/', $className) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
};





