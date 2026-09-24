<?php

/**
 * Email domain lists used by THP\Core\Validation\EmailDomainList.
 *
 * Committed and versioned. NOT a secret. Changes ship with a normal release.
 * Runtime adjustments: 'thp_core_free_mail_domains' and 'thp_core_blocked_domains' filters.
 *
 * Domains are lower-case, with no leading "@".
 */

defined('ABSPATH') || exit;

return [
    // Consumer / free-mail providers: rejected where a company email is required.
    'free_mail' => [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'icloud.com',
        'me.com',
        'aol.com',
        'gmx.de',
        'gmx.net',
        'web.de',
        't-online.de',
    ],

    // Always-rejected domains (e.g. disposable-mail providers).
    'blocked' => [],
];
