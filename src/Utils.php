<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

use stdClass;

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

    /**
     * @return array<string, Reference|Schema>
     * @throws UnresolvableReferenceException
     */
    public static function resolveSchemaProperties(Schema|Reference $schema): array
    {
        if ($schema instanceof Reference) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $schema = $schema->resolve();
            if ($schema === null) {
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
            if ($property->readOnly && !$include_readonly) {
                continue;
            }

            if ($property->example !== null) {
                $value = $property->example;
            } elseif ($property->default !== null) {
                $value = $property->default;
            } else {
                $value = match ($property->type) {
                    'null' => null,
                    'boolean' => true,
                    'object' => new stdClass(),
                    'array' => [],
                    'number' => 1,
                    default => 'string',
                };
            }

            $json_properties[$name] = $value;
        }

        return $json_properties;
    }
}
