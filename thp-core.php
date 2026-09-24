<?php

/**
 * Plugin Name: THP Core
 * Description: Shared building blocks (origin checks, rate limiting, email validation, credentials, safe logging) for THP plugins.
 * Version:     0.1.0
 * Author:      THP
 * Requires PHP: 8.0
 */

namespace THP\Core;

defined('ABSPATH') || exit;

define('THP_CORE_VERSION', '0.1.0');
define('THP_CORE_DIR', __DIR__ . '/thp-core');
define('THP_CORE_CONFIG_DIR', __DIR__ . '/config');

spl_autoload_register(static function (string $class): void {
    $prefix = 'THP\\Core\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = THP_CORE_DIR . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});

/*
 * The autoloader above is registered immediately, so THP\Core classes are
 * usable by any code that runs after this file, including other mu-plugins.
 *
 * Hook-dependent bootstrap is deferred to plugins_loaded: it is the first
 * action that fires after ALL mu-plugins and regular plugins (thp-register,
 * thp-pricing-calculator, ...) have been included, so consumers have had a
 * chance to add filters (e.g. thp_core_free_mail_domains) and the pluggable
 * functions exist. It still fires before init / rest_api_init, which is where
 * the consuming REST endpoints are registered.
 *
 * Priority 0 so Core is ready before consumers' default-priority
 * plugins_loaded callbacks.
 */
add_action('plugins_loaded', [Core::class, 'init'], 0);
