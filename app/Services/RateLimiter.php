<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;

final class RateLimiter
{
    public static function tooManySubmissions(string $ipHash): bool
    {
        return Link::countRecentByIpHash($ipHash) >= 5;
    }

    public static function tooManyDailySubmissions(string $ipHash): bool
    {
        return Link::countDailyByIpHash($ipHash) >= 20;
    }

    public static function formWasSubmittedTooFast(int $startedAt): bool
    {
        return $startedAt <= 0 || (time() - $startedAt) < 3;
    }
}
