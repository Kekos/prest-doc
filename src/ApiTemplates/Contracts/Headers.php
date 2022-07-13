<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;

interface Headers
{
    public function renderHeaders(OpenApi $open_api): string;
}
