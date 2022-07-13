<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

final class SchemaProperty
{
    /**
     * @param string[] $enums
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $schema_reference,
        public readonly bool $type_is_array,
        public readonly bool $required,
        public readonly bool $read_only,
        public readonly ?array $enums,
        public readonly ?string $description,
    ) {
    }
}
