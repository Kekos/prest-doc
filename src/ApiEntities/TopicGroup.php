<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities;

use cebe\openapi\spec\PathItem;

final class TopicGroup
{
    /**
     * @param array<string, PathItem> $topics
     */
    public function __construct(
        public readonly string $name,
        public readonly array $topics,
    ) {
    }
}
