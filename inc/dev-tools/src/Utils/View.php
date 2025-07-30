<?php

namespace NeonWebId\DevTools\Utils;

use function wp_die;

final class View
{
    /**
     * The path to the views directory.
     *
     * @var Path
     */
    private Path $path;

    /**
     * The URI for the views.
     *
     * @var Uri
     */
    private Uri $uri;

    /**
     * Create a new View instance.
     *
     * @param Path $path The path utility for resolving view paths.
     * @param Uri  $uri  The URI utility for generating view URIs.
     */
    public function __construct(Path $path, Uri $uri)
    {
        $this->path = $path;
        $this->uri  = $uri;
    }

    /**
     * Render a view file with the given data.
     *
     * @param string $view The name of the view file (without .php extension).
     * @param array  $data An associative array of data to be extracted into the view.
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $view     = $view . '.php';
        $viewPath = $this->path->get('views/' . $view);
        if (file_exists($viewPath)) {
            extract($data);
            include $viewPath;
        } else {
            wp_die(
                sprintf(
                    __('View file %s not found.', 'dev-tools'),
                    esc_html($viewPath)
                ),
                __('View Error', 'dev-tools'),
                ['response' => 404]
            );
        }
    }
}