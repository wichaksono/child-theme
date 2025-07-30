<?php
namespace NeonWebId\DevTools\Utils;

final class Path
{
    /**
     * The base path for the application.
     *
     * @var string
     */
    private string $basePath;

    /**
     * Create a new Path instance.
     *
     * @param string $basePath The base path for the application.
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Get the full path for a given relative path.
     *
     * @param string $path The relative path to resolve.
     *
     * @return string The full path.
     */
    public function get(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }

    /**
     * Get the full path for an asset.
     *
     * @param string $asset The relative path to the asset.
     *
     * @return string The full path to the asset.
     */
    public function getAsset(string $asset): string
    {
        return $this->get('assets/' . ltrim($asset, '/'));
    }

    /**
     * Get the full path for a view file.
     *
     * @param string $view The relative path to the view file.
     *
     * @return string The full path to the view file.
     */
    public function getView(string $view): string
    {
        return $this->get('views/' . ltrim($view, '/'));
    }
}