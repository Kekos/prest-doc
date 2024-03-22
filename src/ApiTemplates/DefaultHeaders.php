<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\Header;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use Kekos\PrestDoc\ApiTemplates\Contracts\Headers;
use Kekos\PrestDoc\Exceptions\ResolveException;

final class DefaultHeaders implements Headers
{
    public function renderHeaders(OpenApi $open_api): string
    {
        if (!$open_api->components?->headers) {
            return '';
        }

        $markdown = <<<MD
## <span id="headers">Headers</span>

MD;

        foreach ($open_api->components->headers as $name => $header) {
            if ($header instanceof Reference) {
                $header = $header->resolve();

                if (!$header instanceof Header) {
                    throw new ResolveException('Could not resolve header');
                }
            }

            $markdown .= <<<MD
### $name

$header->description

MD;
        }

        return $markdown;
    }
}
