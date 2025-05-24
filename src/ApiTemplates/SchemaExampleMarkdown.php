<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\Utils;

use function is_scalar;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

final class SchemaExampleMarkdown
{
    public function __construct(
        private readonly MediaType $content,
    ) {
    }

    public function renderSchemaExample(bool $include_readonly): string
    {
        if ($this->content->example) {
            if (!is_scalar($this->content->example)) {
                return '';
            }

            return sprintf("%s\n", $this->content->example);
        }

        if (!$this->content->schema) {
            return '';
        }

        $schema = $this->content->schema;

        if ($schema instanceof Reference) {
            $schema = $schema->resolve();
            if (!$schema instanceof Schema) {
                return '';
            }
        }

        if (!$schema->properties) {
            $example_data = Utils::getPropertyExampleData($schema, $include_readonly);
            if (is_scalar($example_data)) {
                return $example_data . PHP_EOL;
            }

            return $this->encodeToString($example_data) . PHP_EOL;
        }

        $example_data = Utils::getSchemaExampleData(
            Utils::resolveSchemaProperties($schema),
            $include_readonly,
        );

        return $this->encodeToString($example_data) . PHP_EOL;
    }

    private function encodeToString(mixed $example_data): string
    {
        return json_encode(
            $example_data,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );
    }
}
