<?php

namespace App\Enums;

/** PHP 7.4-compatible constants; physical equipment states remain separate. */
final class DocumentStatus
{
    const DRAFT = 'DRAFT';
    const ASSIGNED = 'ASSIGNED';
    const IN_PROGRESS = 'IN_PROGRESS';
    const SUBMITTED = 'SUBMITTED';
    const RETURNED = 'RETURNED';
    const VERIFIED = 'VERIFIED';
    const APPROVED = 'APPROVED';
    const SAP_POSTED = 'SAP_POSTED';
    const CANCELLED = 'CANCELLED';
    const REPORT_TRANSITIONS = ['submit' => [self::DRAFT, self::RETURNED], 'return' => [self::SUBMITTED, self::VERIFIED], 'verify' => [self::SUBMITTED], 'approve' => [self::VERIFIED], 'revise' => [self::APPROVED]];
}
