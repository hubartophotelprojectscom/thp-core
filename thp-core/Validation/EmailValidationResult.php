<?php

namespace THP\Core\Validation;

defined('ABSPATH') || exit;

/**
 * Immutable result of EmailVerification::validate().
 *
 * Reasons are stable, machine-readable codes (the REASON_* constants), meant
 * for mapping to translated user-facing messages in the consuming plugin.
 * They never contain the email address itself, so a result is safe to log.
 */
class EmailValidationResult
{
    public const REASON_INVALID_SYNTAX = 'invalid_syntax';
    public const REASON_FREE_MAIL      = 'free_mail_domain';
    public const REASON_BLOCKED_DOMAIN = 'blocked_domain';
    public const REASON_NO_MX          = 'no_mx';

    private bool $is_valid;

    /** @var string[] */
    private array $reasons;

    /**
     * @param bool     $is_valid Overall verdict.
     * @param string[] $reasons  REASON_* codes explaining a failure. Empty when valid.
     */
    public function __construct(bool $is_valid, array $reasons = [])
    {
        $this->is_valid = $is_valid;
        $this->reasons  = $reasons;
    }

    /**
     * Return the overall verdict.
     *
     * @return bool True if the email passed every check that was run.
     */
    public function is_valid(): bool
    {
        return $this->is_valid;
    }

    /**
     * Return the failure reasons.
     *
     * @return string[] REASON_* codes, in check order.
     */
    public function get_reasons(): array
    {
        return $this->reasons;
    }
}
