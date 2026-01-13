<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\ApiEntities\TemplateViewModels\SchemaViewModel;
use Kekos\PrestDoc\ApiTemplates\Contracts\SchemaTemplate;
use Kekos\PrestDoc\Utils;

use function implode;
use function json_encode;
use function sprintf;
use function str_replace;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

final class DefaultSchema implements SchemaTemplate
{
    public function __construct(
        private readonly SchemaViewModel $view_model,
    ) {
    }

    public function renderSchema(Schema|Reference $schema, string $schema_name): string
    {
        $schema = Utils::resolveReference($schema);
        $properties = Utils::resolveSchemaProperties($schema);
        $schema_slug = Utils::slugify($schema_name);

        $json_example = json_encode(
            Utils::getSchemaExampleData($properties),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );

        $properties_md_table = [];

        foreach ($this->view_model->getSchemaProperties($schema, $properties) as $property) {
            $type = ($property->type_is_array ? sprintf('Array of %s', $property->type) : $property->type);
            if ($property->schema_reference) {
                $type = sprintf('<a href="#schema_%s">%s</a>', Utils::slugify($property->schema_reference), $type);
            }

            $required = ($property->required ? 'Yes' : 'No');

            $restrictions = ($property->read_only ? 'read-only' : 'none');
            if ($property->enums) {
                $restrictions .= sprintf('<br>enum, one of `%s`', implode('`, `', $property->enums));
            }

            $description = ($property->description ? str_replace("\n", '<br>', $property->description) : '');

            $properties_md_table[] = <<<MD
| `$property->name` | $type | $required | $restrictions | $description |
MD;
        }

        $properties_md_table = implode(PHP_EOL, $properties_md_table);

        return <<<MD

### <a id="schema_$schema_slug">$schema_name</a>

<div class="prest-doc-code-sample">

```json
$json_example
```

</div>

$schema->description

**Properties**

<div class="prest-doc-table prest-doc-propertes-table">

| Name | Type | Required | Restrictions | Description |
| ---- | ---- | -------- | ------------ | ----------- |
$properties_md_table

</div>

MD;
    }
}
