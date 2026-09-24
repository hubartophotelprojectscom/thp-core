<?php

namespace THP\Core\Validation;

defined('ABSPATH') || exit;

/**
 * Access to the versioned email domain lists.
 *
 * Source of truth: THP_CORE_CONFIG_DIR . '/thp-core-domain-lists.php', a
 * committed (non-secret) file that returns an array keyed by list name:
 *
 *     ['free_mail' => [...], 'blocked' => [...]]
 *
 * The file should be loaded once per request and memoised. Consumers can
 * adjust the lists without a thp-core release through filters:
 *
 *     apply_filters('thp_core_free_mail_domains', array $domains)
 *     apply_filters('thp_core_blocked_domains', array $domains)
 *
 * Returned domains are lower-cased, trimmed and de-duplicated.
 */
class EmailDomainList
{
    /**
     * Return the free-mail domain list from config/thp-core-domain-lists.php ('free_mail'), filtered through 'thp_core_free_mail_domains'.
     *
     * @return string[] Lower-cased domains, e.g. ["gmail.com", ...].
     */
    public static function get_free_mail_domains(): array
    {
        return [];
    }

    /**
     * Check whether a domain is on any deny-list ('free_mail' or 'blocked', e.g. disposable providers).
     *
     * @param string $domain Bare domain (not an email address), any case.
     * @return bool True if the domain must be rejected for company-only forms.
     */
    public static function is_domain_blocked(string $domain): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }
}
