<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;

interface Authentication
{
    public function renderAuthentication(OpenApi $open_api): string;
}
