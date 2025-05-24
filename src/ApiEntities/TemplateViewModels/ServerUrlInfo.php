<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

final class ServerUrlInfo
{
    public function __construct(
        public readonly string $host,
        public readonly string $base_path,
    ) {
    }
}
