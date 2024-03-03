<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;

interface Schemas
{
    public function getSchemas(OpenApi $open_api): string;
}
