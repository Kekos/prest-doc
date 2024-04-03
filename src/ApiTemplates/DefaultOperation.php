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

use function array_unique;
use function basename;
use function implode;
use function is_scalar;
use function json_encode;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
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

`$http_method_uppercase $operation_view_model->path`

<div class="prest-doc-code-sample">

**Code samples**

MD;

        foreach ($this->view_model->getConsumers($operation) as $consumer) {
            $markdown .= <<<MD
```http
$http_method_uppercase $operation_view_model->path HTTP/1.1
Host: $operation_view_model->server_url

MD;

            $producers = [];
            foreach ($operation->responses ?? [] as $response) {
                foreach ($response->content as $content_type => $content) {
                    $producers[] = $content_type;
                }
            }

            if ($producers) {
                $markdown .= sprintf("Accept: %s\n", implode(',', array_unique($producers)));
            }

            if ($consumer) {
                $markdown .= sprintf("Content-Type: %s\n", $consumer);
            }

            $operation_defined_auth = false;
            foreach ($operation->parameters as $parameter) {
                if ($parameter instanceof Reference) {
                    $parameter = $parameter->resolve();
                }

                if ($parameter instanceof Parameter && $parameter->in === 'header') {
                    if (strtolower($parameter->name) === 'authorization') {
                        $operation_defined_auth = true;
                    }

                    $markdown .= sprintf("%s: %s\n", $parameter->name, (is_scalar($parameter->example) ? $parameter->example : ''));
                }
            }

            foreach ($operation_view_model->auth_examples as $header => $example) {
                if ($operation_defined_auth && strtolower($header) === 'authorization') {
                    continue;
                }

                $markdown .= sprintf("%s: %s\n", $header, $example);
            }

            $markdown .= PHP_EOL;

            if (isset($operation->requestBody->content[$consumer])) {
                $content = $operation->requestBody->content[$consumer];

                if ($content->example) {
                    $markdown .= sprintf("%s\n", $content->example);
                } elseif ($content instanceof MediaType && $content->schema) {
                    $include_readonly = ($operation_view_model->method === 'GET');

                    $markdown .= json_encode(
                            Utils::getSchemaExampleData(
                                Utils::resolveSchemaProperties($content->schema), $include_readonly
                            ),
                            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ) . PHP_EOL;
                }
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

                $type = ($parameter->schema->type ?? '');
                $required = ($parameter->required ? 'Yes' : 'No');
                $description = ($parameter->description ?? '');

                if ($parameter->schema->enum) {
                    $type .= sprintf('<br>enum, one of `%s`', implode(', ', $parameter->schema->enum));
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
                    $schema = $content->schema;
                    if ($schema instanceof Schema && $schema->type === 'array') {
                        if (!$schema->items) {
                            continue;
                        }

                        $response_example = [
                            Utils::getSchemaExampleData(Utils::resolveSchemaProperties($schema->items)),
                        ];
                    } else {
                        if (!$schema) {
                            continue;
                        }

                        $response_example = Utils::getSchemaExampleData(
                            Utils::resolveSchemaProperties($schema)
                        );
                    }

                    $json_example = json_encode(
                        $response_example,
                        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                    );

                    $markdown .= <<<MD
$status response

```json
$json_example
```

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

            if ($one_of_required_security = $this->view_model->getRequiredAuth($operation, $operation_view_model->security_schemes)) {
                $security_md .= "**Authentication required**\n\n";

                $first_or = true;
                $last_one_of_key = array_key_last($one_of_required_security);
                foreach ($one_of_required_security as $one_of_key => $all_of_schemes) {
                    if (!$first_or && $last_one_of_key === $one_of_key) {
                        $security_md .= "\n\n*...or...*\n\n";
                    }

                    foreach ($all_of_schemes as $security_name => $security_scheme) {
                        $security_md .= sprintf(
                            "* <a href=\"#authentication_%s\">%s</a>\n", $security_name,
                            $security_scheme->name
                        );
                    }

                    $first_or = false;
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
