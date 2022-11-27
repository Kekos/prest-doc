<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

use Kekos\PrestDoc\Exceptions\TopicException;

use function array_keys;
use function array_reduce;
use function count;
use function current;

final class ApiTaggedTopicsRepository
{
    /** @var array<string, array<string, PathItem>> */
    private array $topics = [];

    public function add(PathItem $path_item, string $path): void
    {
        /** @var string[] $topics */
        $topics = array_keys(
            array_reduce(
                $path_item->getOperations(),
                static function (array $carry, Operation $operation) {
                    if ($operation->tags) {
                        $carry[$operation->tags[0]] = true;
                    }

                    return $carry;
                },
                [],
            )
        );

        if (!($topic = current($topics))) {
            return;
        }

        if (count($topics) > 1) {
            throw TopicException::forTooManyOptions($topics, $path);
        }

        $this->topics[$topic][$path] = $path_item;
    }

    /**
     * @return array<string, array<string, PathItem>>
     */
    public function getTopics(): array
    {
        return $this->topics;
    }
}
