<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Exceptions;

use function sprintf;

final class TemplateRenderException extends BaseException
{
    public static function forNotAString(string $path): self
    {
        return new self(
            sprintf(
                'The PHP template "%s" did not render a string',
                $path,
            )
        );
    }
}
