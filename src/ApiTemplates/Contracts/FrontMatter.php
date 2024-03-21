<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;

interface FrontMatter
{
    public function renderFrontMatter(OpenApi $open_api): string;
}
