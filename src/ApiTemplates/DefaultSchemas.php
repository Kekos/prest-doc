<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\Utils;

use function basename;
use function implode;
use function in_array;
use function json_encode;

use function sprintf;

use function str_replace;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

final class DefaultSchemas implements Contracts\Schemas
{
    public function renderSchemas(OpenApi $open_api): string
    {
        $markdown = <<<MD
## <span id="schemas">Schemas</span>

MD;

        foreach ($open_api->components->schemas as $schema_name => $schema) {
            $properties = Utils::resolveSchemaProperties($schema);
            $schema_slug = Utils::slugify($schema_name);

            $json_example = json_encode(
                Utils::getSchemaExampleData($properties),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            );

            $properties_md_table = [];

            foreach ($properties as $property_name => $property) {
                $is_array = false;
                $ref_schema = null;

                if ($property instanceof Schema && $property->type === 'array') {
                    $items = $property->items;
                    $is_array = true;
                    $property = $items;
                }

                if ($property instanceof Reference) {
                    $name = basename($property->getReference());
                    $ref_schema = $name;
                    $property = $property->resolve();
                } else {
                    $name = $property->type;
                }

                $type = ($is_array ? sprintf('Array of %s', $name) : $name);
                if ($ref_schema) {
                    $type = sprintf('<a href="#schema_%s">%s</a>', Utils::slugify($ref_schema), $type);
                }

                $required = ($schema->required && in_array($property_name, $schema->required, true) ? 'Yes' : 'No');

                $restrictions = ($property->readOnly ? 'read-only' : 'none');
                if ($property->enum) {
                    $restrictions .= sprintf('<br>enum, one of `%s`', implode(', ', $property->enum));
                }

                $description = ($property->description ? str_replace("\n", '<br>', $property->description) : '');

                $properties_md_table[] = <<<MD
| `$property_name` | $type | $required | $restrictions | $description |
MD;
            }

            $properties_md_table = implode(PHP_EOL, $properties_md_table);

            $markdown .= <<<MD

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

        return $markdown;
    }
}
