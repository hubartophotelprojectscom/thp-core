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
 */
class EmailVerification
{
    /**
     * Check RFC-ish syntax (is_email() / FILTER_VALIDATE_EMAIL). No network access.
     *
     * @param string $email Raw email address.
     * @return bool True if the address is syntactically valid.
     */
    public static function is_syntactically_valid(string $email): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Check that the address is syntactically valid and its domain is not on any deny-list (free-mail or blocked).
     *
     * @param string $email Raw email address.
     * @return bool True if the address looks like a business address.
     */
    public static function is_company_email(string $email): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Check whether the address's domain is on the free-mail list (EmailDomainList::get_free_mail_domains()).
     *
     * @param string $email Raw email address.
     * @return bool True if the domain is a known free-mail provider.
     */
    public static function is_free_mail_domain(string $email): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Check that the domain can receive mail: an MX record, or an A/AAAA fallback (RFC 5321 implicit MX).
     *
     * Results are cached per domain in a transient ("thp_core_mx_" . hash of the
     * domain) when $use_cache is true. A DNS lookup failure (timeout, as
     * opposed to NXDOMAIN) should be treated as "valid" and left uncached, so
     * that a DNS outage never blocks signups.
     *
     * @param string $email     Raw email address.
     * @param bool   $use_cache Whether to read and write the per-domain cache.
     * @return bool True if the domain appears able to receive mail.
     */
    public static function has_valid_mx(string $email, bool $use_cache = true): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * Run the configured checks and return every failure reason, not just the first.
     *
     * Supported $options (all optional):
     * - 'require_company' (bool, default true):  reject free-mail and blocked domains.
     * - 'check_mx'        (bool, default true):  run has_valid_mx().
     * - 'use_mx_cache'    (bool, default true):  passed to has_valid_mx().
     *
     * @param string               $email   Raw email address.
     * @param array<string, mixed> $options See above.
     * @return EmailValidationResult
     */
    public static function validate(string $email, array $options = []): EmailValidationResult
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
