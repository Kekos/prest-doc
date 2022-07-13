<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use Kekos\PrestDoc\Exceptions\TopicException;
use Kekos\PrestDoc\Utils;

use function array_keys;
use function array_reduce;
use function count;
use function current;
use function sprintf;
use function str_starts_with;
use function substr;

final class TaggedTopicsRepository implements TopicsRepository
{
    public const TOPIC_PREFIX = 'topic-';

    private const TOPIC_PREFIX_LENGTH = 6;

    /** @var array<string, array<string, PathItem>> */
    private array $topics = [];

    public function addPath(PathItem $path_item, string $path): void
    {
        /** @var string[] $topics */
        $topics = array_keys(
            array_reduce(
                $path_item->getOperations(),
                function (array $carry, Operation $operation) {
                    if ($topic = $this->getTopicTag($operation)) {
                        $carry[$topic] = true;
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

    public function getTopics(): TopicsCollection
    {
        $topics = [];

        foreach ($this->topics as $topic => $path_items) {
            $slug = sprintf('topic_%s', Utils::slugify($topic));
            $topics[] = new TopicGroup($topic, $slug, $path_items);
        }

        return new TopicsCollection($topics);
    }

    private function getTopicTag(Operation $operation): ?string
    {
        if (!$operation->tags) {
            return null;
        }

        foreach ($operation->tags as $tag) {
            if (str_starts_with($tag, self::TOPIC_PREFIX)) {
                return substr($tag, self::TOPIC_PREFIX_LENGTH);
            }
        }

        return null;
    }
}
