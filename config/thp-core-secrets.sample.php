<?php

/**
 * thp-core secrets: TEMPLATE ONLY. Never put real values in this file.
 *
 * Copy this file to config/thp-core-secrets.php ON THE SERVER, in
 * wp-content/mu-plugins/config/ next to thp-core.php, and fill in the values
 * there. The real file:
 *   - is git-ignored (.gitignore), so it is never committed;
 *   - is excluded from deploys (.deployrc DEPLOY_EXCLUDE), so a deploy never
 *     overwrites or deletes it (deploys run without --delete);
 *   - is read lazily by THP\Core\Credentials\Credentials.
 *
 * Every constant is optional. A missing constant makes Credentials::get()
 * return null, and each feature must degrade gracefully or fail closed.
 */

defined('ABSPATH') || exit;

// HubSpot private app access token used for lead/contact sync (Credentials::get('HUBSPOT_TOKEN')).
// define('THP_CORE_HUBSPOT_TOKEN', '');

// Random secret (at least 32 bytes, e.g. bin2hex(random_bytes(32))) used as the HMAC pepper for hashing IPs in logs and rate-limit keys (Credentials::get('LOG_PEPPER')).
// define('THP_CORE_LOG_PEPPER', '');
