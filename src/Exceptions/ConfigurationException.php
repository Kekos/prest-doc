<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Exceptions;

use Kekos\PrestDoc\Configuration;

use function sprintf;

final class ConfigurationException extends BaseException
{
    public static function forFileNotFound(string $path): self
    {
        return new self(
            sprintf(
                'The configuration file "%s" was not found',
                $path,
            )
        );
    }

    public static function forFileReturn(string $path): self
    {
        return new self(
            sprintf(
                'The configuration file "%s" did not return an instance of "%s"',
                $path,
                Configuration::class,
            )
        );
    }

    public static function forClassNotFound(string $class_name): self
    {
        return new self(
            sprintf(
                'No definition for class "%s" was found in the container.',
                $class_name,
            )
        );
    }
}
