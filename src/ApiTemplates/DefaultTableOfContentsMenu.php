<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiEntities\TopicsCollection;
use Kekos\PrestDoc\ApiTemplates\Contracts\TableOfContentsMenu;

use Kekos\PrestDoc\Utils;

use function sprintf;

use const PHP_EOL;

final class DefaultTableOfContentsMenu implements TableOfContentsMenu
{
    public function renderTableOfContentsMenu(OpenApi $open_api, TopicsCollection $topics): string
    {
        $markdown = <<<MD
<div class="prest-doc-topics-menu">
    <nav>


MD;

        if ($open_api->components?->securitySchemes) {
            $markdown .= "* [Authentication](#authentication)\n";
        }

        if ($open_api->components?->headers) {
            $markdown .= "* [Headers](#headers)\n";
        }

        foreach ($topics->groups as $topic_group) {
            $markdown .= sprintf("* [%s](#%s)\n", $topic_group->name, $topic_group->url_fragment);

            foreach ($topic_group->topics as $path_item) {
                foreach ($path_item->getOperations() as $operation) {
                    $operation_slug = Utils::slugify($operation->operationId);

                    $markdown .= sprintf("\t* [%s](#op_%s)\n", $operation->summary, $operation_slug);
                }
            }
        }

        $markdown .= "* [Schemas](#schemas)\n";

        foreach ($open_api->components?->schemas ?? [] as $schema_name => $schema) {
            $schema_slug = Utils::slugify($schema_name);

            $markdown .= sprintf("\t* [%s](#schema_%s)\n", $schema_name, $schema_slug);
        }

        $markdown .= <<<MD

</nav>
</div>
MD;

        return $markdown . PHP_EOL;
    }
}
