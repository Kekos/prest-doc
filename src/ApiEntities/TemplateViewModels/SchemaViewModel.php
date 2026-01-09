<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\Exceptions\ResolveException;

use function array_keys;
use function array_map;
use function basename;
use function in_array;
use function sprintf;

final class SchemaViewModel
{
    /**
     * @param array<string, Schema|Reference> $properties
     * @return array<int, SchemaProperty>
     */
    public function getSchemaProperties(Schema $schema, array $properties): array
    {
        return array_map(function (Schema|Reference $property, string $property_name) use ($schema) {
            return $this->resolveSchemaProperty($schema, $property, $property_name);
        }, $properties, array_keys($properties));
    }

    private function resolveSchemaProperty(Schema $schema, Schema|Reference $property, string $property_name): SchemaProperty
    {
        $is_array = false;
        $ref_schema = null;

        if ($property instanceof Schema && $property->type === 'array') {
            $items = $property->items;
            $is_array = true;
            $property = $items;
        }

        if ($property instanceof Reference) {
            $type = basename($property->getReference());
            $ref_schema = $type;
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $property = $property->resolve();

            if (!$property instanceof Schema) {
                throw new ResolveException(sprintf('Could not resolve schema property `%s`', $property_name));
            }
        } else {
            if (!$property) {
                throw new ResolveException(sprintf('Could not resolve schema property `%s`', $property_name));
            }

            $type = $property->type;
        }

        $required = ($schema->required && in_array($property_name, $schema->required, true));

        if ($schema->allOf) {
            foreach ($schema->allOf as $all_of_ref) {
                if ($all_of_ref instanceof Reference) {
                    $all_of_ref = $all_of_ref->resolve();
                }

                if ($all_of_ref instanceof Schema && isset($all_of_ref->properties[$property_name])) {
                    $required = ($all_of_ref->required && in_array($property_name, $all_of_ref->required, true));
                    if ($required) {
                        break;
                    }
                }
            }
        }

        return new SchemaProperty(
            $property_name,
            $type,
            $ref_schema,
            $is_array,
            $required,
            $property->readOnly,
            $property->enum,
            $property->description,
        );
    }
}
