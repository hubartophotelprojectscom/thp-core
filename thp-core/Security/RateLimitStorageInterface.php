<?php

namespace THP\Core\Security;

defined('ABSPATH') || exit;

/**
 * Storage backend contract for RateLimiter.
 *
 * Keys passed in are already hashed/opaque (never raw PII). Implementations
 * may add their own prefix.
 *
 * Window semantics (fixed window): the expiry is set when a key is first
 * created, and later increments within the window must NOT extend it. A
 * naive set_transient() on every increment resets the TTL and turns this
 * into a sliding window, which lets a slow, steady attacker keep a bucket
 * open forever.
 *
 * Atomicity is best-effort and backend-dependent. Implementations should
 * document whether increment() is atomic under concurrent requests.
 */
interface RateLimitStorageInterface
{
    /**
     * Return the current count for the key.
     *
     * @param string $key Opaque bucket key.
     * @return int Current count, or 0 if the key is absent or expired.
     */
    public function get(string $key): int;

    /**
     * Increment the key's count, creating it with a $window_seconds expiry if it is absent.
     *
     * @param string $key            Opaque bucket key.
     * @param int    $window_seconds Expiry for a newly created key; ignored for an existing one.
     * @return int Count after incrementing.
     */
    public function increment(string $key, int $window_seconds): int;

    /**
     * Delete the key.
     *
     * @param string $key Opaque bucket key.
     * @return void
     */
    public function reset(string $key): void;
}
