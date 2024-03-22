<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities;

use cebe\openapi\spec\PathItem;

final class SingleTopicRepository implements TopicsRepository
{
    /** @var array<string, PathItem> */
    private array $paths = [];

    public function addPath(PathItem $path_item, string $path): void
    {
        $this->paths[$path] = $path_item;
    }

    public function getTopics(): TopicsCollection
    {
        $topics = [
            new TopicGroup('Operations', 'operations', $this->paths),
        ];

        return new TopicsCollection($topics);
    }
}
