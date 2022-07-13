<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

final class Configuration
{
    /**
     * @param array<class-string, object> $api_templates_class_map
     * @param array<int, string> $exclude_paths
     */
    public function __construct(
        public readonly array $api_templates_class_map = [],
        public readonly array $exclude_paths = [],
    ) {
    }
}
