<?php
namespace NeonWebId\DevTools\Utils;

use function wp_die;

final class View
{
    private string $basePath;
    private string $baseUri;

    public function __construct(string $basePath, string $baseUri)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->baseUri = rtrim($baseUri, '/');
    }

    public function getPath(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }

    public function getUri(string $uri): string
    {
        return $this->baseUri . '/' . ltrim($uri, '/');
    }

    public function getAsset(string $asset): string
    {
        return $this->getUri('assets/' . ltrim($asset, '/'));
    }

    public function render(string $view, array $data = []): void
    {
        $view = $view . '.php';
        $viewPath = $this->getPath('views/' . $view);
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