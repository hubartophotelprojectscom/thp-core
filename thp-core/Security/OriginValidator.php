<?php

namespace THP\Core\Security;

defined('ABSPATH') || exit;

/**
 * Origin / Referer validation for public REST endpoints (lead forms, trial
 * signup, pricing calculator submissions).
 *
 * Policy: FAIL-CLOSED.
 * - A request is allowed only when its origin (from the Origin header, or
 *   failing that the scheme+host+port of the Referer header) exactly matches
 *   an entry in the allowlist.
 * - A request with NO Origin and NO Referer header is treated as NOT allowed
 *   for public lead endpoints. The same applies to the literal Origin value
 *   "null" (sandboxed iframes, file://, some redirects).
 * - Server-to-server callers, which send no browser headers, will need an
 *   explicit allowlist mechanism (for example a signed header or a
 *   credential from Credentials). That mechanism is NOT implemented in
 *   this pass, so such callers are rejected for now.
 *
 * Comparison is exact on the normalised origin (lower-cased scheme and host,
 * explicit port only if non-default, no path and no trailing slash). No
 * substring, prefix or suffix matching: "tophotelprojects.com.evil.tld" must
 * never match.
 *
 * This check is a CSRF / drive-by mitigation, NOT authentication. Origin and
 * Referer can be forged by non-browser clients, so always combine it with
 * RateLimiter on public endpoints.
 *
 * Intended use as a REST permission_callback:
 *
 *     'permission_callback' => [OriginValidator::class, 'require_valid_origin'],
 */
class OriginValidator
{
    /**
     * Extract the normalised request origin from the Origin header, falling back to the Referer's scheme+host+port.
     *
     * @param \WP_REST_Request $request Incoming REST request.
     * @return string|null Normalised origin (e.g. "https://tophotelprojects.com"), or null if neither header is present/parseable or Origin is "null".
     */
    public static function get_request_origin(\WP_REST_Request $request): ?string
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Return the normalised default allowlist: the site's own home_url() origin, passed through the 'thp_core_allowed_origins' filter.
     *
     * @return string[] List of normalised origins.
     */
    public static function get_allowed_origins(): array
    {
        return [];
    }

    /**
     * Check the request's origin against an allowlist, using exact matching and failing closed when no origin can be determined.
     *
     * @param \WP_REST_Request $request         Incoming REST request.
     * @param string[]         $allowed_origins Allowlist to check against. An empty array means get_allowed_origins().
     * @return bool True only if the request origin is present and exactly matches an allowed origin.
     */
    public static function is_allowed_origin(\WP_REST_Request $request, array $allowed_origins = []): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * permission_callback helper: return true for an allowed origin, otherwise a 403 WP_Error ('thp_core_forbidden_origin').
     *
     * @param \WP_REST_Request $request Incoming REST request.
     * @return true|\WP_Error
     */
    public static function require_valid_origin(\WP_REST_Request $request): bool|\WP_Error
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
