<?php

namespace THP\Core\Validation;

defined('ABSPATH') || exit;

/**
 * Email address checks for lead and signup forms.
 *
 * The individual checks are small, composable predicates. validate() runs
 * them in order (syntax, then domain lists, then MX) and collects the
 * reasons for failure in a EmailValidationResult, using the
 * EmailValidationResult::REASON_* codes.
 *
 * Domains are compared lower-cased. IDN domains are compared in their
 * punycode (ASCII) form where the intl extension is available.
 *
 * Division of labour: list membership lives in EmailDomainList, which owns the
 * committed lists and the domain() parser. This class owns the checks that act
 * on an address, including everything that touches DNS. The two halves share
 * only EmailDomainList::domain().
 *
 * DNS failure is fail-open by construction, not by consumer convention.
 * has_mail_exchanger() returns null for "could not determine", and every
 * higher-level entry point here treats null as valid. A resolver outage must
 * never cost a real signup.
 */
class EmailVerification
{
    /**
     * A domain that must resolve. Used to tell a genuine "this domain has no mail
     * records" apart from "our resolver is broken", because checkdnsrr() returns
     * false for both.
     */
    private const DNS_CONTROL_DOMAIN = 'cloudflare.com';

    /** Transient key prefix for cached MX lookups. */
    private const MX_CACHE_PREFIX = 'thp_core_mx_';

    /**
     * Check RFC-ish syntax (is_email() / FILTER_VALIDATE_EMAIL). No network access.
     *
     * @param string $email Raw email address.
     * @return bool True if the address is syntactically valid.
     */
    public static function is_syntactically_valid(string $email): bool
    {
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        // WordPress' is_email() is the authority in a WP request; fall back to the
        // PHP filter so the class stays usable in tests and WP-CLI bootstraps.
        if (function_exists('is_email')) {
            return (bool) is_email($email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check that the address is syntactically valid and its domain is not on any deny-list (free-mail or blocked).
     *
     * @param string $email Raw email address.
     * @return bool True if the address looks like a business address.
     */
    public static function is_company_email(string $email): bool
    {
        if (! self::is_syntactically_valid($email)) {
            return false;
        }

        $domain = EmailDomainList::domain($email);

        if ($domain === '') {
            return false;
        }

        return ! EmailDomainList::is_domain_blocked($domain);
    }

    /**
     * Check whether the address's domain is on the free-mail list (EmailDomainList::get_free_mail_domains()).
     *
     * @param string $email Raw email address.
     * @return bool True if the domain is a known free-mail provider.
     */
    public static function is_free_mail_domain(string $email): bool
    {
        return EmailDomainList::is_free_provider($email);
    }

    /**
     * Check that the domain can receive mail: an MX record, or an A/AAAA fallback (RFC 5321 implicit MX).
     *
     * Fail-open wrapper around has_mail_exchanger(): an undetermined lookup
     * (null) counts as valid. Use has_mail_exchanger() directly when the caller
     * needs to tell "no mail records" apart from "could not check".
     *
     * @param string $email     Raw email address.
     * @param bool   $use_cache Whether to read and write the per-domain cache.
     * @return bool True if the domain appears able to receive mail.
     */
    public static function has_valid_mx(string $email, bool $use_cache = true): bool
    {
        $domain = EmailDomainList::domain($email);

        if ($domain === '') {
            return true;
        }

        return self::has_mail_exchanger($domain, $use_cache) ?? true;
    }

    /**
     * Resolve whether a domain can receive mail, keeping "undetermined" distinct from "no".
     *
     * Three states, deliberately:
     *   true  - the domain has an MX record, or an A record (RFC 5321 §5.1:
     *           with no MX, a host with an A record still accepts mail).
     *   false - the resolver works and the domain has neither. A real negative.
     *   null  - the lookup could not be performed, or the resolver looks broken.
     *           Callers must treat this as valid.
     *
     * checkdnsrr() reports "lookup failed" and "no such record" identically, so
     * an empty answer alone proves nothing. Before returning a real negative we
     * confirm the resolver by looking up DNS_CONTROL_DOMAIN, which must resolve;
     * if that also comes back empty the problem is on our side, not theirs.
     *
     * Results are cached per domain for 24h. The undetermined case is
     * deliberately NOT cached, so a transient resolver failure does not persist.
     *
     * @param string $domain    Bare domain (not an email address).
     * @param bool   $use_cache Whether to read and write the per-domain cache.
     * @return bool|null True/false when the answer is trustworthy, null when undetermined.
     */
    public static function has_mail_exchanger(string $domain, bool $use_cache = true): ?bool
    {
        $domain = rtrim(strtolower(trim($domain)), '.');

        if ($domain === '') {
            return null;
        }

        $cache_key = self::MX_CACHE_PREFIX . md5($domain);

        if ($use_cache && function_exists('get_transient')) {
            $cached = get_transient($cache_key);

            if ($cached === '1') {
                return true;
            }

            if ($cached === '0') {
                return false;
            }
        }

        $resolved = self::lookup($domain);

        if ($resolved === null) {
            return null;
        }

        if ($use_cache && function_exists('set_transient')) {
            set_transient($cache_key, $resolved ? '1' : '0', self::cache_ttl());
        }

        return $resolved;
    }

    /**
     * Run the configured checks and return every failure reason, not just the first.
     *
     * Supported $options (all optional):
     * - 'require_company' (bool, default true):  reject free-mail and blocked domains.
     * - 'check_mx'        (bool, default true):  run has_mail_exchanger().
     * - 'use_mx_cache'    (bool, default true):  passed through to the MX check.
     *
     * A syntactically invalid address short-circuits: with no usable domain the
     * remaining checks would only restate the same problem in other words.
     *
     * An undetermined MX lookup adds no reason, so validate() fails open on DNS
     * exactly as has_valid_mx() does.
     *
     * @param string               $email   Raw email address.
     * @param array<string, mixed> $options See above.
     * @return EmailValidationResult
     */
    public static function validate(string $email, array $options = []): EmailValidationResult
    {
        $require_company = (bool) ($options['require_company'] ?? true);
        $check_mx        = (bool) ($options['check_mx'] ?? true);
        $use_mx_cache    = (bool) ($options['use_mx_cache'] ?? true);

        $domain = EmailDomainList::domain($email);

        if (! self::is_syntactically_valid($email) || $domain === '') {
            return new EmailValidationResult(false, [EmailValidationResult::REASON_INVALID_SYNTAX]);
        }

        $reasons = [];

        if ($require_company) {
            // Checked independently so a domain on both lists reports both, per
            // "every failure reason, not just the first".
            if (EmailDomainList::is_free_provider($email)) {
                $reasons[] = EmailValidationResult::REASON_FREE_MAIL;
            }

            if (in_array($domain, EmailDomainList::get_blocked_domains(), true)) {
                $reasons[] = EmailValidationResult::REASON_BLOCKED_DOMAIN;
            }
        }

        if ($check_mx && self::has_mail_exchanger($domain, $use_mx_cache) === false) {
            $reasons[] = EmailValidationResult::REASON_NO_MX;
        }

        return new EmailValidationResult($reasons === [], $reasons);
    }

    /**
     * How long a resolved MX lookup stays cached, in seconds.
     *
     * Reads WordPress' DAY_IN_SECONDS when available so the value tracks core,
     * with the same literal as a fallback outside a WP bootstrap.
     *
     * @return int
     */
    private static function cache_ttl(): int
    {
        return defined('DAY_IN_SECONDS') ? (int) DAY_IN_SECONDS : 86400;
    }

    /**
     * Resolve mail records for a domain.
     *
     * @param string $domain Bare, already normalised domain.
     * @return bool|null True/false when the answer is trustworthy, null when the
     *                   lookup could not be performed or the resolver looks broken.
     */
    private static function lookup(string $domain): ?bool
    {
        if (self::has_record($domain, 'MX')) {
            return true;
        }

        // RFC 5321 §5.1: with no MX record, a host with an A record still accepts mail.
        if (self::has_record($domain, 'A')) {
            return true;
        }

        if (! self::dns_available()) {
            return null;
        }

        // Both lookups came back empty. Confirm the resolver actually works before
        // calling this a real negative, since checkdnsrr() reports failure and
        // "no such record" identically.
        if (! self::has_record(self::DNS_CONTROL_DOMAIN, 'A')) {
            return null;
        }

        return false;
    }

    /**
     * Is any DNS lookup function available? Some managed hosts disable them.
     *
     * @return bool
     */
    private static function dns_available(): bool
    {
        return function_exists('checkdnsrr') || function_exists('dns_get_record');
    }

    /**
     * Look up a single record type for a domain.
     *
     * @param string $domain Bare domain.
     * @param string $type   'MX' or 'A'.
     * @return bool True if at least one record of that type exists.
     */
    private static function has_record(string $domain, string $type): bool
    {
        if (function_exists('checkdnsrr')) {
            return (bool) @checkdnsrr($domain, $type);
        }

        if (function_exists('dns_get_record')) {
            $constant = $type === 'MX' ? DNS_MX : DNS_A;
            $records  = @dns_get_record($domain, $constant);

            return is_array($records) && $records !== [];
        }

        return false;
    }
}
