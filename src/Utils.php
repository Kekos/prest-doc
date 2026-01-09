<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\Exceptions\ResolveException;

use function current;
use function is_scalar;
use function mb_strtolower;
use function rawurlencode;
use function str_replace;

final class Utils
{
    private function __construct()
    {
    }

    public static function slugify(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        /** @var string $str */
        $str = str_replace([' ', '--'], '-', $str);

        return rawurlencode($str);
    }

    public static function resolveReference(Schema|Reference $schema): Schema
    {
        if ($schema instanceof Schema) {
            return $schema;
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $schema = $schema->resolve();

        if (!$schema instanceof Schema) {
            throw new ResolveException('The resolved reference type is not supported.');
        }

        return $schema;
    }

    /**
     * @return array<string, Reference|Schema>
     * @throws UnresolvableReferenceException
     */
    public static function resolveSchemaProperties(Schema|Reference $schema): array
    {
        if ($schema instanceof Reference) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $schema = $schema->resolve();
            if (!$schema instanceof Schema) {
                return [];
            }
        }

        /** @var array<string, Reference|Schema> $properties */
        $properties = [];

        foreach ($schema->properties as $property_name => $property) {
            $properties[$property_name] = $property;
        }

        if ($schema->allOf) {
            foreach ($schema->allOf as $all_of_ref) {
                if ($all_of_ref instanceof Reference) {
                    $all_of_ref = $all_of_ref->resolve();
                }

                if ($all_of_ref instanceof Schema) {
                    foreach ($all_of_ref->properties as $property_name => $property) {
                        $properties[$property_name] = $property;
                    }
                }
            }
        }

        return $properties;
    }

    /**
     * @param array<string, Reference|Schema> $properties
     * @return array<string, mixed>
     */
    public static function getSchemaExampleData(array $properties, bool $include_readonly = true): array
    {
        $json_properties = [];

        foreach ($properties as $name => $property) {
            if ($property instanceof Reference) {
                continue;
            }

            if ($property->readOnly && !$include_readonly) {
                continue;
            }

            $json_properties[$name] = self::getPropertyExampleData($property);
        }

        return $json_properties;
    }

    public static function getPropertyExampleData(Schema $property, bool $include_readonly = true): mixed
    {
        if ($property->example !== null) {
            return self::getCastedPropertyExampleData($property, $property->example);
        }

        if ($property->default !== null) {
            return self::getCastedPropertyExampleData($property, $property->default);
        }

        if ($property->enum) {
            return self::getCastedPropertyExampleData($property, current($property->enum));
        }

        if ($property->items && $property->type === 'array') {
            return [self::getSchemaExampleData(self::resolveSchemaProperties($property->items), $include_readonly)];
        }

        return match ($property->type) {
            'null' => null,
            'boolean' => true,
            'object' => self::getSchemaExampleData(self::resolveSchemaProperties($property), $include_readonly),
            'array' => [],
            'integer' => 1,
            'number' => 1.42,
            default => 'string',
        };
    }

    public static function getCastedPropertyExampleData(Schema $property, mixed $example_data): mixed
    {
        if (!is_scalar($example_data)) {
            return $example_data;
        }

        return match ($property->type) {
            'integer' => (int) $example_data,
            'number' => (float) $example_data,
            default => $example_data,
        };
    }
}
