<?php

namespace THP\Core\Validation;

use THP\Core\Logging\SafeLogger;

defined('ABSPATH') || exit;

/**
 * Access to the versioned email domain lists.
 *
 * Source of truth: THP_CORE_PACKAGE_CONFIG_DIR . '/thp-core-domain-lists.php',
 * a committed (non-secret) file that returns an array keyed by list name:
 *
 *     ['free_mail' => [...], 'blocked' => [...]]
 *
 * The file is loaded once per request and memoised. Consumers can adjust the
 * lists without a thp-core release through filters:
 *
 *     apply_filters('thp_core_free_mail_domains', array $domains)
 *     apply_filters('thp_core_blocked_domains', array $domains)
 *
 * Returned domains are lower-cased, trimmed and de-duplicated.
 *
 * This class holds list membership only. It answers "is this domain on a
 * list", never "should this signup be rejected": the legacy register form
 * rejects free-mail addresses while the signup wizard accepts them and asks
 * for a company name instead, so the same fact drives opposite policies. The
 * policy stays with each consumer.
 *
 * Every method is a pure static with lazy memoisation and no bootstrap step,
 * so all of them are safe to call the moment the autoloader can see the class,
 * including before Core::init() runs. There is no partial-init state.
 *
 * A read failure is never silent. If the config file is missing, unreadable or
 * malformed, the lists degrade to empty AND an error is logged, so that
 * "nothing is configured" (legitimate, if unlikely) stays distinguishable from
 * "something broke". Consumers can also test config_read_failed() directly.
 */
class EmailDomainList
{
    /** Filename of the committed list file inside THP_CORE_PACKAGE_CONFIG_DIR. */
    private const CONFIG_FILE = 'thp-core-domain-lists.php';

    /** Channel used for this class's SafeLogger output. */
    private const LOG_CHANNEL = 'core';

    /** @var array{free_mail: string[], blocked: string[]}|null Memoised lists, pre-filter. */
    private static ?array $lists = null;

    /** @var array<string, bool>|null Memoised lookup index for the free-mail list. */
    private static ?array $free_index = null;

    /** @var array<string, bool>|null Memoised lookup index for the combined deny-list. */
    private static ?array $blocked_index = null;

    /** Whether the memoised config read failed, as opposed to returning empty lists by design. */
    private static bool $read_failed = false;

    /**
     * Extract the lower-cased domain part of an email address.
     *
     * Returns an empty string when the value has no usable domain; callers treat
     * that as "cannot judge" and leave the address alone.
     *
     * @param mixed $email Raw email address, any type (request data is untrusted).
     * @return string Lower-cased bare domain, or "" when there is none.
     */
    public static function domain(mixed $email): string
    {
        if (! is_scalar($email)) {
            return '';
        }

        $email = trim((string) $email);
        $at    = strrpos($email, '@');

        if ($at === false) {
            return '';
        }

        $domain = strtolower(trim(substr($email, $at + 1)));

        // Strip a trailing dot from a fully qualified name so "gmail.com." and
        // "gmail.com" are treated as the same domain.
        $domain = rtrim($domain, '.');

        return $domain !== '' && str_contains($domain, '.') ? $domain : '';
    }

    /**
     * Return the free-mail domain list from the committed config ('free_mail'), filtered through 'thp_core_free_mail_domains'.
     *
     * @return string[] Lower-cased domains, e.g. ["gmail.com", ...].
     */
    public static function get_free_mail_domains(): array
    {
        return self::filtered('free_mail', 'thp_core_free_mail_domains');
    }

    /**
     * Return the always-blocked domain list from the committed config ('blocked'), filtered through 'thp_core_blocked_domains'.
     *
     * Free-mail providers are NOT included here; this is the deny-list proper
     * (disposable-mail providers and similar). Use is_domain_blocked() for the
     * combined verdict.
     *
     * @return string[] Lower-cased domains.
     */
    public static function get_blocked_domains(): array
    {
        return self::filtered('blocked', 'thp_core_blocked_domains');
    }

    /**
     * Check whether an address is hosted by a known free-mail provider.
     *
     * Reports membership only. Whether that should reject the submission, or
     * merely require a company name, is the consumer's decision.
     *
     * @param mixed $email Raw email address, any type.
     * @return bool True if the address's domain is on the free-mail list.
     */
    public static function is_free_provider(mixed $email): bool
    {
        $domain = self::domain($email);

        if ($domain === '') {
            return false;
        }

        if (self::$free_index === null) {
            self::$free_index = array_fill_keys(self::get_free_mail_domains(), true);
        }

        return isset(self::$free_index[$domain]);
    }

    /**
     * Check whether a domain is on any deny-list ('free_mail' or 'blocked', e.g. disposable providers).
     *
     * @param string $domain Bare domain (not an email address), any case.
     * @return bool True if the domain must be rejected for company-only forms.
     */
    public static function is_domain_blocked(string $domain): bool
    {
        $domain = rtrim(strtolower(trim($domain)), '.');

        if ($domain === '') {
            return false;
        }

        if (self::$blocked_index === null) {
            self::$blocked_index = array_fill_keys(
                array_merge(self::get_free_mail_domains(), self::get_blocked_domains()),
                true
            );
        }

        return isset(self::$blocked_index[$domain]);
    }

    /**
     * Report whether the committed config file could not be read or was malformed.
     *
     * Lets a consumer tell an empty list that is empty by design from one that
     * is empty because the read failed, without having to scrape the log.
     *
     * @return bool True if the memoised read failed.
     */
    public static function config_read_failed(): bool
    {
        self::lists();

        return self::$read_failed;
    }

    /**
     * Drop the memoised lists so the next call re-reads the config. Test seam.
     *
     * @return void
     */
    public static function flush_cache(): void
    {
        self::$lists         = null;
        self::$free_index    = null;
        self::$blocked_index = null;
        self::$read_failed   = false;
    }

    /**
     * Read one list and run it through its filter, normalising both sides.
     *
     * The filter result is type-checked and re-normalised: a consumer that
     * returns a non-array, or junk entries, degrades to the config value rather
     * than corrupting the list.
     *
     * @param string $key         List key in the config file.
     * @param string $filter_name Filter applied to the list.
     * @return string[] Lower-cased, de-duplicated domains.
     */
    private static function filtered(string $key, string $filter_name): array
    {
        $domains = self::lists()[$key];

        if (! function_exists('apply_filters')) {
            return $domains;
        }

        /**
         * Filter a thp-core email domain list.
         *
         * Lets deployments extend or trim a list without a thp-core release.
         *
         * @param string[] $domains Lower-cased domains.
         */
        $filtered = apply_filters($filter_name, $domains);

        return is_array($filtered) ? self::normalise($filtered) : $domains;
    }

    /**
     * Load and memoise the committed config file.
     *
     * @return array{free_mail: string[], blocked: string[]}
     */
    private static function lists(): array
    {
        if (self::$lists !== null) {
            return self::$lists;
        }

        // Default to empty rather than leaving the property null, so even a
        // failed read leaves a usable, fully-formed structure behind.
        self::$lists       = ['free_mail' => [], 'blocked' => []];
        self::$read_failed = false;

        $file = self::config_path();

        if (! is_readable($file)) {
            self::$read_failed = true;
            self::log_read_failure('config file missing or unreadable', $file);

            return self::$lists;
        }

        // require, not require_once: the result is memoised here, and
        // require_once would yield bool true on any later include.
        $raw = require $file;

        if (! is_array($raw)) {
            self::$read_failed = true;
            self::log_read_failure('config file did not return an array', $file);

            return self::$lists;
        }

        foreach (['free_mail', 'blocked'] as $key) {
            if (! array_key_exists($key, $raw)) {
                // A missing key is a malformed file, not a list that is empty
                // by design, so it counts as a read failure.
                self::$read_failed = true;
                self::log_read_failure(sprintf('config file has no "%s" key', $key), $file);

                continue;
            }

            if (! is_array($raw[$key])) {
                self::$read_failed = true;
                self::log_read_failure(sprintf('config key "%s" is not an array', $key), $file);

                continue;
            }

            self::$lists[$key] = self::normalise($raw[$key]);
        }

        return self::$lists;
    }

    /**
     * Absolute path to the committed list file.
     *
     * Uses THP_CORE_PACKAGE_CONFIG_DIR (inside the package) and not
     * THP_CORE_CONFIG_DIR, which points at the hand-placed secrets directory
     * beside the package. Falls back to a path derived from this file, so the
     * class still resolves when loaded without the plugin loader (tests, WP-CLI).
     *
     * @return string
     */
    private static function config_path(): string
    {
        $dir = defined('THP_CORE_PACKAGE_CONFIG_DIR')
            ? THP_CORE_PACKAGE_CONFIG_DIR
            : dirname(__DIR__) . '/config';

        return rtrim((string) $dir, '/\\') . '/' . self::CONFIG_FILE;
    }

    /**
     * Report a config read failure.
     *
     * Logging must never turn a degraded list into a fatal error, so a logger
     * that is unavailable or still a scaffold falls back to error_log(). The
     * path is not PII and is safe to record.
     *
     * @param string $problem Static, PII-free description.
     * @param string $file    Absolute path that was read.
     * @return void
     */
    private static function log_read_failure(string $problem, string $file): void
    {
        $message = 'Email domain list unavailable: ' . $problem;

        try {
            SafeLogger::error(self::LOG_CHANNEL, $message, ['file' => $file]);

            return;
        } catch (\Throwable $e) {
            // SafeLogger is still a scaffold (every method throws). Fall through
            // so the failure is recorded either way; this becomes dead code once
            // Logging is implemented.
        }

        error_log('[thp-core][' . self::LOG_CHANNEL . '][ERROR] ' . $message . ' ' . $file);
    }

    /**
     * Lower-case, trim, drop non-strings and empties, de-duplicate.
     *
     * @param array<mixed> $domains
     * @return string[]
     */
    private static function normalise(array $domains): array
    {
        $clean = [];

        foreach ($domains as $domain) {
            if (! is_string($domain)) {
                continue;
            }

            // Tolerate a leading "@" so a filtered-in "@gmail.com" still matches.
            $domain = strtolower(trim($domain));
            $domain = rtrim(ltrim($domain, '@'), '.');

            if ($domain !== '') {
                $clean[] = $domain;
            }
        }

        return array_values(array_unique($clean));
    }
}
