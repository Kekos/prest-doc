<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\ApiEntities\TemplateViewModels\OperationsViewModel;
use Kekos\PrestDoc\ApiEntities\TemplateViewModels\TopicOperationViewModel;
use Kekos\PrestDoc\Exceptions\ResolveException;
use Kekos\PrestDoc\Utils;

use function basename;
use function current;
use function implode;
use function is_scalar;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

use const PHP_EOL;

final class DefaultOperation implements Contracts\OperationTemplate
{
    public function __construct(
        private readonly OperationsViewModel $view_model,
    ) {
    }

    public function renderOperation(TopicOperationViewModel $operation_view_model): string
    {
        $operation = $operation_view_model->operation;
        $operation_slug = Utils::slugify($operation->operationId);
        $http_method_uppercase = strtoupper($operation_view_model->method);

        $markdown = <<<MD
### <a id="op_$operation_slug">$operation->summary</a>

`$http_method_uppercase {$operation_view_model->server_url->base_path}$operation_view_model->path`

<div class="prest-doc-code-sample">

**Code samples**

MD;

        foreach ($this->view_model->getConsumers($operation) as $consumer) {
            $markdown .= <<<MD
```http
$http_method_uppercase {$operation_view_model->server_url->base_path}$operation_view_model->path HTTP/1.1
Host: {$operation_view_model->server_url->host}

MD;

            $producers = [];
            foreach ($operation->responses ?? [] as $response) {
                foreach ($response->content as $content_type => $content) {
                    $producers[] = $content_type;
                }
            }

            if ($producers) {
                $markdown .= sprintf("Accept: %s\n", current($producers));
            }

            $operation_defined_auth = false;
            $operation_defined_content_type = false;
            foreach ($operation->parameters as $parameter) {
                if ($parameter instanceof Reference) {
                    $parameter = $parameter->resolve();
                }

                if ($parameter instanceof Parameter && $parameter->in === 'header') {
                    if (strtolower($parameter->name) === 'authorization') {
                        $operation_defined_auth = true;
                    }

                    if (strtolower($parameter->name) === 'content-type') {
                        $operation_defined_content_type = true;
                    }

                    $markdown .= sprintf("%s: %s\n", $parameter->name, (is_scalar($parameter->example) ? $parameter->example : ''));
                }
            }

            if ($consumer && !$operation_defined_content_type) {
                $markdown .= sprintf("Content-Type: %s\n", $consumer);
            }

            foreach ($this->view_model->getAuthExamples($operation_view_model) as $header => $example) {
                if ($operation_defined_auth && strtolower($header) === 'authorization') {
                    continue;
                }

                $markdown .= sprintf("%s: %s\n", $header, $example);
            }

            $markdown .= PHP_EOL;

            if (isset($operation->requestBody->content[$consumer])) {
                $content = $operation->requestBody->content[$consumer];

                $example_renderer = new SchemaExampleMarkdown($content);
                $markdown .= $example_renderer->renderSchemaExample($operation_view_model->method === 'GET');
            }

            $markdown .= "```\n";
        }

        $markdown .= "</div>\n\n";

        if ($operation->deprecated) {
            $markdown .= "**DEPRECATED**\n\n";
        }

        $markdown .= sprintf("%s\n\n", $operation->description);

        if ($operation->parameters || $operation->requestBody instanceof RequestBody) {
            $parameters_md_table = [];

            foreach ($operation->parameters as $parameter) {
                if ($parameter instanceof Reference) {
                    $parameter = $parameter->resolve();

                    if (!$parameter instanceof Parameter) {
                        throw new ResolveException('The parameter could not be resolved');
                    }
                }

                $parameter_schema = $parameter->schema;
                if ($parameter_schema instanceof Reference) {
                    $parameter_schema = $parameter_schema->resolve();

                    if (!$parameter_schema instanceof Schema) {
                        throw new ResolveException('The parameter schema could not be resolved');
                    }
                }

                $type = ($parameter_schema->type ?? '');
                $required = ($parameter->required ? 'Yes' : 'No');
                $description = ($parameter->description ?? '');

                if ($parameter_schema?->enum) {
                    $type .= sprintf('<br>enum, one of `%s`', implode(', ', $parameter_schema->enum));
                }

                $parameters_md_table[] = <<<MD
| `$parameter->name` | $parameter->in | $type | $required | $description |
MD;
            }

            if ($operation->requestBody instanceof RequestBody) {
                foreach ($operation->requestBody->content as $content_type => $content) {
                    if (!$content instanceof MediaType) {
                        continue;
                    }

                    $type_md = '';
                    $ref_schema = null;
                    if ($content->schema) {
                        $schema = $content->schema;

                        $type_md = '<br>';

                        if ($schema instanceof Reference) {
                            $name = basename($schema->getReference());
                            $ref_schema = $name;
                            $schema = $schema->resolve();
                        } else {
                            $name = $schema->type;
                        }

                        if ($schema instanceof Schema) {
                            if ($ref_schema) {
                                $type_md .= '[';
                            }

                            $type_md .= $name;

                            if ($schema->format) {
                                $type_md .= sprintf(' (%s)', $schema->format);
                            }

                            if ($ref_schema) {
                                $type_md .= sprintf('](#schema_%s)', Utils::slugify($ref_schema));
                            }
                        }
                    }

                    $required = ($operation->requestBody->required ? 'Yes' : 'No');
                    $description = '';
                    if ($operation->requestBody->description) {
                        $description = str_replace("\n", '<br>', $operation->requestBody->description);
                    }

                    $parameters_md_table[] = <<<MD
| body | body | `$content_type`$type_md | $required | $description |
MD;

                    if (!$ref_schema && $content->schema instanceof Schema) {
                        foreach ($content->schema->properties as $name => $property) {
                            $property = Utils::resolveReference($property);
                            $required = ($property->required ? 'Yes' : 'No');
                            $description = '';
                            if ($property->description) {
                                $description = str_replace("\n", '<br>', $property->description);
                            }

                            $parameters_md_table[] = <<<MD
| » `$name` | body | $property->type | $required | $description |
MD;
                        }
                    }
                }
            }

            $parameters_md_table = implode(PHP_EOL, $parameters_md_table);

            $markdown .= <<<MD
**Parameters**

<div class="prest-doc-table prest-doc-parameters-table">

| Name | In | Type | Required | Description |
| ---- | -- | ---- | -------- | ----------- |
$parameters_md_table

</div>

MD;
        }

        if ($operation->responses) {
            $markdown .= <<<MD
<div class="prest-doc-code-sample">

**Example responses**


MD;

            foreach ($operation->responses as $status => $response) {
                foreach ($response->content as $content) {
                    $example_renderer = new SchemaExampleMarkdown($content);
                    $json_example = $example_renderer->renderSchemaExample(true);

                    $markdown .= <<<MD
$status response

```json
$json_example```

MD;
                }
            }

            $responses_md_table = [];

            foreach ($operation->responses as $status => $response) {
                $response_schema = '';

                foreach ($response->content as $content) {
                    $schema = $content->schema;
                    $is_array = false;
                    $ref_schema = null;

                    if ($schema instanceof Schema && $schema->type === 'array') {
                        $items = $schema->items;
                        $is_array = true;
                        $schema = $items;
                    }

                    if ($schema instanceof Reference) {
                        $name = basename($schema->getReference());
                        $ref_schema = $name;
                    } else {
                        if (!$schema) {
                            continue;
                        }

                        $name = $schema->type;
                    }

                    if ($ref_schema) {
                        $response_schema = '[';
                    }

                    if ($is_array) {
                        $response_schema .= sprintf('Array of %s', $name);
                    } else {
                        $response_schema .= $name;
                    }

                    if ($ref_schema) {
                        $response_schema .= sprintf('](#schema_%s)', Utils::slugify($ref_schema));
                    }
                }

                $responses_md_table[] = <<<MD
| $status | $response->description | $response_schema |
MD;
            }

            $responses_md_table = implode(PHP_EOL, $responses_md_table);
            $security_md = '';

            $security_requirements = $operation_view_model->security_requirements;
            if ($security_requirements->hasAny()) {
                $security_md .= "**Authentication required**\n\n";

                if ($security_requirements->requirements_local) {
                    foreach ($security_requirements->requirements_local as $security_name => $security_scheme) {
                        $security_md .= sprintf(
                            "* <a href=\"#authentication_%s\">%s</a>\n", $security_name,
                            $security_scheme->name
                        );
                    }

                    if ($security_requirements->requirements_global) {
                        $security_md .= "\n\n*...or...*\n\n";
                    }
                }

                foreach ($security_requirements->requirements_global as $security_name => $security_scheme) {
                    $security_md .= sprintf(
                        "* <a href=\"#authentication_%s\">%s</a>\n", $security_name,
                        $security_scheme->name
                    );
                }
            }

            $markdown .= <<<MD

</div>

**Responses**

<div class="prest-doc-table prest-doc-responses-table">

| Status | Description | Schema |
| ------ | ----------- | ------ |
$responses_md_table

$security_md

</div>


MD;
        }

        return $markdown;
    }
}
