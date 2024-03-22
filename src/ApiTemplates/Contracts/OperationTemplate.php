<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates\Contracts;

use Kekos\PrestDoc\ApiEntities\TemplateViewModels\TopicOperationViewModel;

interface OperationTemplate
{
    public function renderOperation(TopicOperationViewModel $operation_view_model): string;
}
