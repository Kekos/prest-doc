<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Server;
use Kekos\PrestDoc\ApiEntities\TopicsCollection;
use Kekos\PrestDoc\Utils;

use function array_reduce;
use function array_unique;
use function basename;
use function implode;
use function is_array;
use function json_encode;
use function parse_url;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;
use const PHP_URL_HOST;

final class DefaultOperations implements Contracts\Operations
{
    public function renderOperations(OpenApi $open_api, TopicsCollection $topics): string
    {
        $server_url = array_reduce(
            $open_api->servers,
            static fn($carry, Server $server) => $server->url,
            'http://localhost',
        );
        $server_url = parse_url($server_url, PHP_URL_HOST);

        $markdown = '';

        foreach ($topics->groups as $group) {
            $topics_slug = Utils::slugify($group->name);
            $markdown .= sprintf("## <a id=\"topic_%s\">%s</a>\n\n", $topics_slug, $group->name);

            foreach ($group->topics as $path => $path_item) {
                foreach ($path_item->getOperations() as $method => $operation) {
                    $operation_slug = Utils::slugify($operation->operationId);
                    $http_method_uppercase = strtoupper($method);

                    $markdown .= <<<MD
### <a id="op_$operation_slug">$operation->summary</a>

`$http_method_uppercase $path`

<div class="prest-doc-code-sample">

**Code samples**

MD;

                    foreach ($this->getConsumers($operation) as $consumer) {
                        $markdown .= <<<MD
```http
$http_method_uppercase $path HTTP/1.1
Host: $server_url

MD;

                        $producers = [];
                        foreach ($operation->responses as $response) {
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

                                $markdown .= sprintf("%s: %s\n", $parameter->name, $parameter->example);
                            }
                        }

                        foreach ($this->getAuthExamples($open_api) as $header => $example) {
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
                                $include_readonly = ($method === 'GET');

                                $markdown .= json_encode(
                                    Utils::getSchemaExampleData(Utils::resolveSchemaProperties($content->schema), $include_readonly),
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
                            }

                            $type = ($parameter->schema->type ?? '');
                            $required = ($parameter->required ? 'Yes' : 'No');
                            $description = ($parameter->description ?? '');

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
                                    $response_example = [
                                        Utils::getSchemaExampleData(Utils::resolveSchemaProperties($schema->items)),
                                    ];
                                } else {
                                    $response_example = Utils::getSchemaExampleData(Utils::resolveSchemaProperties($schema));
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

                        if (is_array($operation->security)) {
                            $security_md .= "**Authentication required**\n\n";

                            $one_of_required_security = [];
                            foreach ($operation->security as $security_requirement) {
                                $all_of_required_security = [];
                                // All schemes in this loop are "OR"
                                foreach ($open_api->components->securitySchemes as $security_name => $security_scheme) {
                                    // All schemes in this loop are "AND"
                                    if (isset($security_requirement->$security_name)) {
                                        $all_of_required_security[$security_name] = $security_scheme;
                                    }
                                }

                                $one_of_required_security[] = $all_of_required_security;
                            }

                            $first_or = true;
                            $last_one_of_key = array_key_last($one_of_required_security);
                            foreach ($one_of_required_security as $one_of_key => $all_of_schemes) {
                                if (!$first_or && $last_one_of_key === $one_of_key) {
                                    $security_md .= "\n\n*...or...*\n\n";
                                }

                                foreach ($all_of_schemes as $security_name => $security_scheme) {
                                    $security_md .= sprintf("* <a href=\"#authentication_%s\">%s</a>\n", $security_name, $security_scheme->name);
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
                }
            }
        }

        return $markdown;
    }

    /**
     * @return non-empty-array<int, string|null>
     */
    private function getConsumers(Operation $operation): array
    {
        if (!($operation->requestBody instanceof RequestBody)) {
            return [null];
        }

        $consumers = [];
        foreach ($operation->requestBody->content as $content_type => $content) {
            $consumers[] = $content_type;
        }

        if (!$consumers) {
            $consumers[] = null;
        }

        return $consumers;
    }

    /**
     * @return array<string, string>
     */
    private function getAuthExamples(OpenApi $open_api): array
    {
        $examples = [];

        foreach ($open_api->components->securitySchemes as $security_scheme) {
            switch ($security_scheme->type) {
                case 'apiKey':
                    if ($security_scheme->in === 'header') {
                        $examples[$security_scheme->name] = 'API_KEY';
                    }

                    break;
                case 'http':
                    if ($security_scheme->in === 'header') {
                        $examples[$security_scheme->name] = sprintf('%s {access-token}', $security_scheme->scheme);
                    }

                    break;
                case 'openIdConnect':
                case 'oauth2':
                    $examples['Authorization'] = 'Bearer {access-token}';
                    break;
            }
        }

        return $examples;
    }
}
