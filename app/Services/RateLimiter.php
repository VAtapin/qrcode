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
}
