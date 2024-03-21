<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use Kekos\PrestDoc\Exceptions\ConfigurationException;

use function is_file;
use function is_readable;

final class ConfigurationLoader
{
    private function __construct()
    {
    }

    public static function fromPath(string $path): Configuration
    {
        if (!is_file($path) || !is_readable($path)) {
            throw ConfigurationException::forFileNotFound($path);
        }

        $config = require $path;

        if (!$config instanceof Configuration) {
            throw ConfigurationException::forFileReturn($path);
        }

        return $config;
    }
}
