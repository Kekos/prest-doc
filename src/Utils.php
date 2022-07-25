<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

use stdClass;

use function mb_strtolower;
use function rawurlencode;
use function str_replace;

final class Utils
{
    private function __construct() {}

    public static function slugify(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        /** @var string $str */
        $str = str_replace([' ', '--'], '-', $str);

        return rawurlencode($str);
    }

    public static function getSchemaProperties(Schema|Reference $schema, bool $include_readonly = true): array
    {
        if ($schema instanceof Reference) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $schema = $schema->resolve();
            if ($schema === null) {
                return [];
            }
        }

        $properties = [];

        foreach ($schema->properties as $name => $property) {
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

            $properties[$name] = $value;
        }

        return $properties;
    }
}
