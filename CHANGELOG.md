# Changelog

## [Unreleased]
### Added
- `THP\Core\Validation\EmailDomainList`: real implementation of the email domain lists. `domain()` parses an address to a lower-cased bare domain, `get_free_mail_domains()` / `get_blocked_domains()` read the committed config and apply the `thp_core_free_mail_domains` / `thp_core_blocked_domains` filters, `is_free_provider()` and `is_domain_blocked()` answer membership. List membership only — reject-vs-require-company policy stays with each consumer
- `THP\Core\Validation\EmailVerification`: real implementation of the address checks. `has_mail_exchanger()` resolves MX with an A-record fallback (RFC 5321 §5.1) and returns three states — `true`, `false`, or `null` when the lookup could not be trusted, confirmed against a control domain so a broken resolver is never mistaken for a real negative. Results cached per domain for 24h under `thp_core_mx_*`; the undetermined case is deliberately not cached. `validate()` collects every failure reason as `EmailValidationResult::REASON_*` codes
- `THP_CORE_PACKAGE_CONFIG_DIR`, for committed non-secret config that ships inside the package
- 13 free-mail domains merged in from thp-register's list: yahoo.co.in, yahoo.co.uk, hotmail.de, hotmail.co.uk, outlook.de, msn.com, proton.me, protonmail.com, mail.ru, yandex.ru, qq.com, 163.com, 126.com (26 entries total)

### Fixed
- The committed domain list was unreachable. `THP_CORE_CONFIG_DIR` resolves to `mu-plugins/config/` (the hand-placed secrets directory), but the list ships inside the package at `mu-plugins/thp-core/config/`. `EmailDomainList` now reads `THP_CORE_PACKAGE_CONFIG_DIR`; the two paths are kept distinct so a deploy can never overwrite the secrets file

### Notes
- DNS failure is fail-open by construction: `null` from `has_mail_exchanger()` means "could not tell" and every caller above it treats that as valid. A resolver outage must never cost a signup
- A failed or malformed config read never returns an empty list silently. It is logged and flagged through `config_read_failed()`, so "no free domains configured" stays distinguishable from "something broke". Logging routes through `SafeLogger` with an `error_log()` fallback, since `Logging\SafeLogger` is still a stub
- Still stubbed, unchanged: `Security\*`, `Credentials\*`, `Logging\*`

## [0.1.0] - 2026-09-24
### Added
- Initial scaffold: loader (thp-core.php), THP\Core autoloader, Thp_Core bootstrap on plugins_loaded
- Stub classes: Thp_Origin_Validator, Thp_Rate_Limiter (+ storage interface, transient storage), Thp_Email_Verification, Thp_Email_Domain_List, Thp_Email_Validation_Result, Thp_Credentials, Thp_Safe_Logger
- config/thp-core-secrets.sample.php (template) and config/thp-core-domain-lists.php (free-mail deny-list)

### Notes
- No real logic yet. Every stub method throws RuntimeException('Not implemented yet'); do not call these from consumers until implemented
- Superseded for the Validation area — see [Unreleased], where EmailDomainList and EmailVerification are implemented
