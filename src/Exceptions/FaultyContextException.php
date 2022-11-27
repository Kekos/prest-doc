<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Exceptions;

use function sprintf;

final class FaultyContextException extends BaseException
{
    public static function forInputDirectory(string $path): self
    {
        return new self(
            sprintf(
                'The input directory "%s" was not found',
                $path,
            )
        );
    }

    public static function forPath(string $path): self
    {
        return new self(
            sprintf(
                'The path "%s" was not found',
                $path,
            )
        );
    }

    public static function forTemplate(string $path): self
    {
        return new self(
            sprintf(
                'Could not find template file "%s"',
                $path,
            )
        );
    }
}
