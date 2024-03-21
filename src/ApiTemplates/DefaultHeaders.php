<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiTemplates\Contracts\Headers;

final class DefaultHeaders implements Headers
{
    public function renderHeaders(OpenApi $open_api): string
    {
        if (!$open_api->components->headers) {
            return '';
        }

        $markdown = <<<MD
## <span id="headers">Headers</span>

MD;

        foreach ($open_api->components->headers as $name => $header) {
            $markdown .= <<<MD
### $name

$header->description

MD;
        }

        return $markdown;
    }
}
