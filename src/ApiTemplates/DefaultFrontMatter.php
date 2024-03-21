<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiTemplates\Contracts\FrontMatter;

final class DefaultFrontMatter implements FrontMatter
{
    public function renderFrontMatter(OpenApi $open_api): string
    {
        $title = $open_api->info->title;
        $version = $open_api->info->version;

        return <<<MD
---
title: $title v$version
---
MD;

    }
}
