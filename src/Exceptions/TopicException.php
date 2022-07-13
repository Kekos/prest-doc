<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Exceptions;

use function implode;
use function sprintf;

final class TopicException extends BaseException
{
    /**
     * @param string[] $topics
     */
    public static function forTooManyOptions(array $topics, string $path): self
    {
        return new self(
            sprintf(
                'The path "%s" assigned multiple topics "%s", this is not allowed',
                $path,
                implode(', ', $topics)
            )
        );
    }
}
