<?php

declare(strict_types=1);

/**
 * Q to me - moderated short link and QR code service.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 * @link https://bible-media.de/
 * @copyright 2026 Atapin Vladimir / Bible Media
 * @version 1.0.0
 */

namespace App\Services;

use App\Models\Link;

/**
 * Provides public form anti-spam checks.
 */
final class RateLimiter
{
    /**
     * Checks the short-term submission limit.
     */
    public static function tooManySubmissions(string $ipHash): bool
    {
        return Link::countRecentByIpHash($ipHash) >= 5;
    }

    /**
     * Checks the daily submission limit.
     */
    public static function tooManyDailySubmissions(string $ipHash): bool
    {
        return Link::countDailyByIpHash($ipHash) >= 20;
    }

    /**
     * Checks whether the form was submitted too quickly for a human.
     */
    public static function formWasSubmittedTooFast(int $startedAt): bool
    {
        return $startedAt <= 0 || (time() - $startedAt) < 3;
    }
}
