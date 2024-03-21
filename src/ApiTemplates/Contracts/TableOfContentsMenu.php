<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiEntities\TopicsCollection;

interface TableOfContentsMenu
{
    public function renderTableOfContentsMenu(OpenApi $open_api, TopicsCollection $topics): string;
}
