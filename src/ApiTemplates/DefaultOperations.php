<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiEntities\TemplateViewModels\OperationsViewModel;
use Kekos\PrestDoc\ApiEntities\TopicsCollection;
use Kekos\PrestDoc\ApiTemplates\Contracts\OperationTemplate;

use function sprintf;

final class DefaultOperations implements Contracts\Operations
{
    public function __construct(
        private readonly OperationsViewModel $view_model,
        private readonly OperationTemplate $operation_renderer,
    ) {
    }

    public function renderOperations(OpenApi $open_api, TopicsCollection $topics): string
    {
        $markdown = '';

        foreach ($topics->groups as $group) {
            $markdown .= sprintf("## <a id=\"%s\">%s</a>\n\n", $group->url_fragment, $group->name);

            foreach ($this->view_model->getOperations($open_api, $group) as $operation_view_model) {
                $markdown .= $this->operation_renderer->renderOperation($operation_view_model);
            }
        }

        return $markdown;
    }
}
