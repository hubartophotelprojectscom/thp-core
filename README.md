# THP Core

Shared logic for the TopHotelProjects plugins, installed as a must-use plugin. It holds code that thp-register (free trial signup) and thp-pricing-calculator would otherwise each carry a copy of: origin validation, rate limiting, company-email validation, secret lookup and PII-safe logging. The upcoming thp-project-snapshot plugin will use it too. Everything is under the `THP\Core\` namespace and the `THP_CORE_*` / `thp_core_*` prefixes, which keeps it separate from thp-security. There is no Composer or build step: `thp-core.php` registers a plain `spl_autoload_register` for `thp-core/`, the same way thp-security does.

## Deployment

This repo sits next to `thp-security` and deploys the same way. Pushing a tag rsyncs the repo root into `wp-content/mu-plugins/` on Raidboxes. The target directory is shared with the other mu-plugins, so deploys run without `--delete`. WordPress only loads PHP files at the top level of `mu-plugins/`, so `thp-core.php` must sit there, and it loads its own `thp-core/` subdirectory. `.deployrc` excludes the real secrets file plus repo-only files (`README.md`, `CHANGELOG.md`, `.gitignore`) that would otherwise overwrite files at the shared `mu-plugins/` root. The `deploy_exclude` list in the CI workflows (adapted by hand from `thp-shared-workflows`) must contain the same entries. The secrets file is placed on the server by hand, outside the deploy, at `wp-content/mu-plugins/config/thp-core-secrets.php`. It is git-ignored and never committed.

## Secrets

Copy `config/thp-core-secrets.sample.php` to `config/thp-core-secrets.php` on the server and define:

| Constant | Purpose |
| --- | --- |
| `THP_CORE_HUBSPOT_TOKEN` | HubSpot private-app access token for lead/contact sync. |
| `THP_CORE_LOG_PEPPER` | Random secret (≥ 32 bytes) used as the HMAC pepper when hashing IPs for logs and rate-limit keys. |

All of them are optional at load time. `Thp_Credentials::get('HUBSPOT_TOKEN')` returns `null` when a constant is missing, and the calling code has to handle that case.
