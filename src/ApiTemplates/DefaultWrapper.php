<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiTemplates\Contracts\Wrapper;

final class DefaultWrapper implements Contracts\Wrapper
{
    public function renderWrapper(OpenApi $open_api, string $content): string
    {
        $title = $open_api->info->title;
        $version = $open_api->info->version;

        return <<<MD
<div class="prest-doc-content">

# $title v$version

$content

[Go to top](#top)

</div>
MD;

    }
}
