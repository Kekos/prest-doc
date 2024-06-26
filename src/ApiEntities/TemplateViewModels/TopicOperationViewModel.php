<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\SecurityScheme;

final class TopicOperationViewModel
{
    /**
     * @param array<string, string> $auth_examples
     * @param Reference[]|SecurityScheme[] $security_schemes
     */
    public function __construct(
        public readonly string $server_url,
        public readonly string $path,
        public readonly string $method,
        public readonly Operation $operation,
        public readonly array $auth_examples,
        public readonly array $security_schemes,
    ) {
    }
}
