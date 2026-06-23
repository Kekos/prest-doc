<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

final class SchemaPropertyType
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $schema_reference = null,
    ) {
    }
}
