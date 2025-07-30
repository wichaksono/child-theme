<?php
namespace NeonWebId\DevTools\Utils;

final class Uri
{
    /**
     * The base URI for the application.
     *
     * @var string
     */
    private string $baseUri;

    /**
     * Create a new Uri instance.
     *
     * @param string $baseUri The base URI for the application.
     */
    public function __construct(string $baseUri)
    {
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * Get the full URI for a given relative URI.
     *
     * @param string $uri The relative URI to resolve.
     *
     * @return string The full URI.
     */
    public function get(string $uri): string
    {
        return $this->baseUri . '/' . ltrim($uri, '/');
    }

    /**
     * Get the full URI for an asset.
     *
     * @param string $asset The relative path to the asset.
     *
     * @return string The full URI to the asset.
     */
    public function getAsset(string $asset): string
    {
        return $this->get('assets/' . ltrim($asset, '/'));
    }
}