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

// Uncomment the line below to enable the Dev Tools panel for all users
// $devTools->showPanelFor([
//     'admin@example.com',
//     'admin',
// ]);

// Uncomment the line below to override the default panel title, name, and view
//$devTools->setGeneralTab([
//    'title' => __('Hi, We are here to help', 'child-theme-name'),
//    'name'  => 'Help & Support',
//    'view'  => 'general', // view file in inc/dev-tools/views/general.php
//]);

$devTools->apply();

/**
 * -------------------------------------
 * Custom Functions and Hooks Start Here
 * -------------------------------------
 */