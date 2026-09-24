# Changelog

## [0.1.0] - 2026-09-24
### Added
- Initial scaffold: loader (thp-core.php), THP\Core autoloader, Thp_Core bootstrap on plugins_loaded
- Stub classes: Thp_Origin_Validator, Thp_Rate_Limiter (+ storage interface, transient storage), Thp_Email_Verification, Thp_Email_Domain_List, Thp_Email_Validation_Result, Thp_Credentials, Thp_Safe_Logger
- config/thp-core-secrets.sample.php (template) and config/thp-core-domain-lists.php (free-mail deny-list)

### Notes
- No real logic yet. Every stub method throws RuntimeException('Not implemented yet'); do not call these from consumers until implemented
