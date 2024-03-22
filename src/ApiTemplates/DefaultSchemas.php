<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiTemplates\Contracts\SchemaTemplate;

final class DefaultSchemas implements Contracts\Schemas
{
    public function __construct(
        private readonly SchemaTemplate $schema_renderer,
    ) {
    }

    public function renderSchemas(OpenApi $open_api): string
    {
        if (!$open_api->components?->schemas) {
            return '';
        }

        $markdown = <<<MD
## <span id="schemas">Schemas</span>

MD;

        foreach ($open_api->components->schemas as $schema_name => $schema) {
            $markdown .= $this->schema_renderer->renderSchema($schema, $schema_name);
        }

        return $markdown;
    }
}
