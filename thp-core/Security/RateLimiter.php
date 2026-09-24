<?php

namespace THP\Core\Security;

defined('ABSPATH') || exit;

/**
 * Fixed-window rate limiter for public endpoints.
 *
 * A "bucket" is an opaque string key (build per-IP keys with key_for_ip()).
 * Counts live in a RateLimitStorageInterface backend, which defaults
 * to TransientRateLimitStorage.
 *
 * Typical usage:
 *
 *     $key = RateLimiter::key_for_ip('register');
 *     if (! RateLimiter::check($key, 5, 15 * MINUTE_IN_SECONDS)) {
 *         return new \WP_Error('thp_core_rate_limited', '...', ['status' => 429]);
 *     }
 *     RateLimiter::hit($key, 15 * MINUTE_IN_SECONDS);
 *
 * PRIVACY: never key on, store or log a raw IP address. key_for_ip() hashes
 * the IP (peppered, via SafeLogger::hash_ip()) before it becomes part of
 * any storage key. Callers that build their own keys must do the same.
 *
 * The storage keys used here are disjoint from thp-security's
 * thp_login_fail_ / thp_login_lock_ transients.
 */
class RateLimiter
{
    /**
     * Report whether the bucket is still under its limit. Read-only: does not count an attempt.
     *
     * @param string $bucket_key     Opaque bucket key (see key_for_ip()).
     * @param int    $max_attempts   Maximum attempts allowed within the window.
     * @param int    $window_seconds Window length in seconds.
     * @return bool True if the request is allowed (current count < $max_attempts).
     */
    public static function check(string $bucket_key, int $max_attempts, int $window_seconds): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Record one attempt against the bucket, starting a new window if none is active.
     *
     * @param string $bucket_key     Opaque bucket key.
     * @param int    $window_seconds Window length in seconds; used only when a new window starts.
     * @return int Attempt count in the current window after this hit.
     */
    public static function hit(string $bucket_key, int $window_seconds): int
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Clear the bucket, e.g. after a successful, verified submission.
     *
     * @param string $bucket_key Opaque bucket key.
     * @return void
     */
    public static function reset(string $bucket_key): void
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Build a bucket key from a prefix and the hashed client IP. The raw IP never appears in the key.
     *
     * When $ip is null, the client IP is resolved from REMOTE_ADDR. Proxy headers
     * such as X-Forwarded-For are NOT trusted unless an explicit trusted-proxy
     * configuration exists (not part of this pass).
     *
     * @param string      $prefix Caller namespace, e.g. "register" or "pricing".
     * @param string|null $ip     Client IP, or null to resolve from the current request.
     * @return string Key such as "register_<hash>"; the storage layer adds its own prefix.
     */
    public static function key_for_ip(string $prefix, ?string $ip = null): string
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
