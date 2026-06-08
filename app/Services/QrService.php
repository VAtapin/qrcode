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

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Generates QR-code PNG files for short links.
 */
final class QrService
{
    /**
     * Generates a QR-code PNG and returns its storage-relative path.
     */
    public function generate(string $shortCode, string $color): string
    {
        $hex = ltrim($color, '#');
        $foreground = new Color(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
        $relative = 'qrcodes/' . hash('sha256', $shortCode) . '.png';
        $target = STORAGE_PATH . '/' . $relative;

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data(url($shortCode))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(320)
            ->margin(12)
            ->foregroundColor($foreground)
            ->backgroundColor(new Color(255, 255, 255))
            ->build();

        $result->saveToFile($target);
        return $relative;
    }
}
