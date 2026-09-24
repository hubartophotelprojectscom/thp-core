<?php

namespace THP\Core\Credentials;

defined('ABSPATH') || exit;

/**
 * Read-only access to secrets.
 *
 * Secrets are PHP constants defined in THP_CORE_CONFIG_DIR .
 * '/thp-core-secrets.php'. That file is NOT in the repo: it is git-ignored,
 * excluded from deploys via .deployrc, and placed on the server out-of-band.
 * See config/thp-core-secrets.sample.php for the expected constant names.
 *
 * - The secrets file is loaded lazily with require_once on the first get() or
 *   has() call. A missing file is not an error.
 * - $key is the constant name WITHOUT the "THP_CORE_" prefix, e.g.
 *   get('HUBSPOT_TOKEN') reads THP_CORE_HUBSPOT_TOKEN. The prefix is
 *   enforced, so this class cannot be used to read unrelated constants
 *   (DB_PASSWORD, AUTH_KEY, ...).
 * - An undefined or empty constant returns null, never a fatal error. Callers
 *   MUST handle null explicitly: degrade gracefully for non-security features,
 *   or fail closed for security features.
 * - Values are never logged, cached in the database or exposed through
 *   filters.
 */
class Credentials
{
    public const CONSTANT_PREFIX = 'THP_CORE_';

    /**
     * Return the value of constant THP_CORE_{$key}, cast to string, or null if it is undefined or empty.
     *
     * @param string $key Constant name without the THP_CORE_ prefix, e.g. "HUBSPOT_TOKEN".
     * @return string|null
     */
    public static function get(string $key): ?string
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Check whether constant THP_CORE_{$key} is defined and non-empty.
     *
     * @param string $key Constant name without the THP_CORE_ prefix.
     * @return bool
     */
    public static function has(string $key): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
