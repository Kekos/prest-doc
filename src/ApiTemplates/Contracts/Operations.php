<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiEntities\TopicsCollection;

interface Operations
{
    public function renderOperations(OpenApi $open_api, TopicsCollection $topics): string;
}
