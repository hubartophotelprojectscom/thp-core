<?php

namespace THP\Core;

defined('ABSPATH') || exit;

/**
 * Facade / bootstrap for thp-core.
 *
 * Owns the explicit, ordered initialisation of any thp-core module that needs
 * to register WordPress hooks. The loader (thp-core.php) only ever calls
 * Core::init(); individual modules are never initialised automatically.
 *
 * Consumers (thp-register, thp-pricing-calculator, thp-project-snapshot) should
 * guard their usage with:
 *
 *     class_exists(\THP\Core\Core::class) && \THP\Core\Core::is_available()
 *
 * and degrade gracefully (or fail closed for security-relevant checks) when
 * thp-core is not deployed.
 */
class Core
{
    private static bool $initialized = false;

    /**
     * Bootstrap thp-core: initialise modules that need hooks, in a fixed order. Idempotent.
     *
     * Runs on plugins_loaded (priority 0). No module needs hooks yet, so this
     * only marks thp-core as initialised. It must never throw, because it runs
     * on every request.
     *
     * @return void
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;
    }

    /**
     * Return the running thp-core version (mirrors the plugin header / THP_CORE_VERSION).
     *
     * @return string Semantic version string, e.g. "0.1.0".
     */
    public static function version(): string
    {
        return THP_CORE_VERSION;
    }

    /**
     * Report whether thp-core is loaded and initialised, so consumers can decide between using it and degrading.
     *
     * @return bool True once init() has run.
     */
    public static function is_available(): bool
    {
        return self::$initialized;
    }
}
