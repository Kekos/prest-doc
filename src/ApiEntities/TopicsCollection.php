<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities;

final class TopicsCollection
{
    /**
     * @param TopicGroup[] $groups
     */
    public function __construct(
        public readonly array $groups,
    ) {
    }
}
