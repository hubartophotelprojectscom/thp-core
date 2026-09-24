<?php

namespace THP\Core\Logging;

defined('ABSPATH') || exit;

/**
 * PII-safe logger for THP plugins.
 *
 * Rules:
 * - $message must NEVER contain raw PII (email addresses, IPs, phone numbers,
 *   names). Keep it a static, greppable string such as "Trial signup rejected".
 * - PII goes only into $context, under the known keys 'email', 'ip' and
 *   'phone'. These are masked or hashed automatically before anything is
 *   written:
 *     'email' => mask_email()
 *     'ip'    => hash_ip()
 *     'phone' => all but the last 2 digits masked
 * - Other context values are written as-is (JSON-encoded), so they must not
 *   contain PII either.
 *
 * Output: a single line through error_log(), e.g.
 *     [thp-core][register][WARNING] Trial signup rejected {"email":"j***@example.com","reason":"free_mail_domain"}
 *
 * hash_ip() uses a peppered HMAC with the pepper from
 * Credentials::get('LOG_PEPPER'), never a hardcoded salt. The same hash
 * is used by RateLimiter::key_for_ip(). Without a pepper, it returns a
 * fixed placeholder rather than an unpeppered hash or the raw IP.
 */
class SafeLogger
{
    /**
     * Log an informational event.
     *
     * @param string               $channel Consumer name, e.g. "register", "pricing", "snapshot".
     * @param string               $message Static, PII-free message.
     * @param array<string, mixed> $context Extra data. PII only under 'email', 'ip' or 'phone'.
     * @return void
     */
    public static function info(string $channel, string $message, array $context = []): void
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Log a recoverable problem, such as a rejected submission or a degraded dependency.
     *
     * @param string               $channel Consumer name.
     * @param string               $message Static, PII-free message.
     * @param array<string, mixed> $context Extra data. PII only under 'email', 'ip' or 'phone'.
     * @return void
     */
    public static function warning(string $channel, string $message, array $context = []): void
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Log a failure that needs attention, such as an API error or missing credentials.
     *
     * @param string               $channel Consumer name.
     * @param string               $message Static, PII-free message.
     * @param array<string, mixed> $context Extra data. PII only under 'email', 'ip' or 'phone'.
     * @return void
     */
    public static function error(string $channel, string $message, array $context = []): void
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Mask an email for logging: keep the first character of the local part and the domain ("jane@acme.com" becomes "j***@acme.com").
     *
     * @param string $email Raw email address. Invalid input returns "***".
     * @return string
     */
    public static function mask_email(string $email): string
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Return a truncated HMAC-SHA256 of the IP, peppered with Credentials::get('LOG_PEPPER'), so it can be correlated but not reversed.
     *
     * @param string $ip Raw IPv4/IPv6 address.
     * @return string Hex hash, or a fixed placeholder if the pepper is not configured.
     */
    public static function hash_ip(string $ip): string
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
