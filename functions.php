<?php
/**
 * Child Theme Functions
 *
 * @package ChildThemeName
 * @since 1.0.0
 */

use NeonWebId\DevTools\Utils\Panel;

defined('ABSPATH') || exit;

/**
 * Dev Tools Loader
 *
 * @package NeonWebId\DevTools
 * @since 1.0.0
 */
require_once __DIR__ . '/inc/dev-tools/autoload.php';

/** @var Panel $devTools */
$devTools = require_once __DIR__ . '/inc/dev-tools/bootstrap.php';

// $devTools->showPanelFor([
//     'admin@example.com',
//     'adminusername',
// ]);

$devTools->apply();

/**
 * -------------------------------------
 * Custom Functions and Hooks Start Here
 * -------------------------------------
 */