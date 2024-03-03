<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities;

use InvalidArgumentException;

use function gettype;
use function is_object;
use function sprintf;

final class TopicsCollection
{
    /**
     * @param TopicGroup[] $groups
     */
    public function __construct(
        public readonly array $groups,
    ) {
        foreach ($this->groups as $i => $group) {
            if ($group instanceof TopicGroup) {
                continue;
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Unexpected %s at element %s, expected TopicGroup',
                    (is_object($group) ? $group::class : gettype($group)),
                    $i,
                )
            );
        }
    }
}
