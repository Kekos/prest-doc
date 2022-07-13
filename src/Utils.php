<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use function mb_strtolower;
use function rawurlencode;
use function str_replace;

final class Utils
{
    private function __construct() {}

    public static function slugify(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        /** @var string $str */
        $str = str_replace([' ', '--'], '-', $str);

        return rawurlencode($str);
    }
}
