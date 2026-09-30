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

/*
 * Two config locations, deliberately distinct.
 *
 * Deployment rsyncs the repo root INTO wp-content/mu-plugins/, so this file
 * lands at mu-plugins/thp-core.php and __DIR__ is mu-plugins/ itself.
 *
 * THP_CORE_CONFIG_DIR  -> mu-plugins/config/
 *     Hand-placed, git-ignored, deploy-excluded secrets
 *     (thp-core-secrets.php). Outside the package on purpose: a deploy must
 *     never be able to overwrite or remove it.
 *
 * THP_CORE_PACKAGE_CONFIG_DIR -> mu-plugins/thp-core/config/
 *     Committed, versioned, non-secret config that ships with a release
 *     (thp-core-domain-lists.php). Inside the package, so it is replaced on
 *     every deploy along with the code that reads it.
 */
define('THP_CORE_CONFIG_DIR', __DIR__ . '/config');
define('THP_CORE_PACKAGE_CONFIG_DIR', THP_CORE_DIR . '/config');

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
 * Bundled shared library: CMB2 (v2.13.2), the admin metabox / options-page
 * framework THP plugins build their settings screens with. It lives here, once,
 * so there is a single copy to upgrade and security-patch rather than one per
 * consuming plugin — the same "shared engine in Core, plugin-specific usage in
 * the consumer" split as the Validation classes above. Only the LIBRARY lives
 * here; each plugin registers its own option pages (on cmb2_admin_init).
 *
 * Loaded through CMB2's own official entry point — we require its init.php and
 * do not hand-roll a loader. CMB2 is explicitly built to be bundled by several
 * plugins/themes at once without a redeclare fatal: init.php wraps everything in
 * `if ( ! class_exists( 'CMB2_Bootstrap_2132', false ) )` (the bootstrap class
 * name carries the version, so copies never collide), each copy hooks its loader
 * on `init` at a PRIORITY that decrements every release (newest version =
 * earliest priority), and that loader bails early on `class_exists( 'CMB2' )`.
 * The newest bundled copy therefore wins and older copies stand down. Requiring
 * it from this mu-plugin (which loads before `init`) makes CMB2 available to
 * every consuming plugin. Centralising here is best practice given that safety
 * net, not a hard requirement — but it keeps us to one copy.
 */
$thpCoreCmb2Init = THP_CORE_DIR . '/lib/CMB2/init.php';
if (is_readable($thpCoreCmb2Init)) {
    require_once $thpCoreCmb2Init;
}

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
