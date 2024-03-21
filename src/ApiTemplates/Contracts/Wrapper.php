<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;

interface Wrapper
{
    public function renderWrapper(OpenApi $open_api, string $content): string;
}
