<?php

namespace THP\Core\Security;

defined('ABSPATH') || exit;

/**
 * WordPress-transient-backed rate limit storage.
 *
 * Transient names are PREFIX . $key, where PREFIX is "thp_core_rl_". Keys must
 * keep the full name within the 172-character transient name limit, so
 * callers should pass hashed keys.
 *
 * To preserve fixed-window semantics (see the interface), the stored value
 * should carry the window's absolute expiry timestamp alongside the count,
 * and later writes should reuse the remaining TTL rather than the full
 * $window_seconds.
 *
 * NOT atomic: two concurrent requests can read the same count and both
 * write count+1. That is acceptable for coarse abuse throttling on lead forms.
 * Use an object-cache-backed implementation (wp_cache_incr) if strict limits
 * are needed.
 */
class TransientRateLimitStorage implements RateLimitStorageInterface
{
    public const PREFIX = 'thp_core_rl_';

    /**
     * Read the count stored in transient PREFIX . $key.
     *
     * @param string $key Opaque bucket key.
     * @return int Current count, or 0 if absent or expired.
     */
    public function get(string $key): int
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Increment the count in transient PREFIX . $key, keeping the original window expiry.
     *
     * @param string $key            Opaque bucket key.
     * @param int    $window_seconds Expiry for a newly created window.
     * @return int Count after incrementing.
     */
    public function increment(string $key, int $window_seconds): int
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Delete transient PREFIX . $key.
     *
     * @param string $key Opaque bucket key.
     * @return void
     */
    public function reset(string $key): void
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
