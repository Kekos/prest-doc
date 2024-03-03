<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities;

use cebe\openapi\spec\PathItem;

interface TopicsRepository
{
    public function addPath(PathItem $path_item, string $path): void;

    public function getTopics(): TopicsCollection;
}
