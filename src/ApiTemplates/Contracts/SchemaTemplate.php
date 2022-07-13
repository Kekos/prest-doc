<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

interface SchemaTemplate
{
    public function renderSchema(Schema|Reference $schema, string $schema_name): string;
}
