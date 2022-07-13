<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Exceptions;

use function error_get_last;
use function sprintf;

final class FilesystemException extends BaseException
{
    public static function forMethod(string $method, string $path): self
    {
        $error = error_get_last();

        return new self(
            sprintf(
                '%s failed for "%s": %s',
                $method,
                $path,
                $error['message'] ?? '',
            )
        );
    }
}
