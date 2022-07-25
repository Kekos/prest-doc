<?php declare(strict_types=1);

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Server;
use Kekos\PrestDoc\Utils;

/**
 * @var OpenApi $openapi
 * @var array<string, array<string, PathItem>>|PathItem[][] $topics_menu
 */

$server_url = array_reduce(
    $openapi->servers,
    static fn($carry, Server $server) => $server->url,
    'http://localhost',
);
$server_url = parse_url($server_url, PHP_URL_HOST);

/** @var array<string, string> $auth_samples */
$auth_samples = [];
?>
---
title: <?php echo $openapi->info->title; ?> v<?php echo $openapi->info->version . PHP_EOL; ?>
---

# <?php echo $openapi->info->title; ?> v<?php echo $openapi->info->version; ?>

<?php if ($topics_menu): ?>
<nav>
<?php if ($openapi->components->securitySchemes): ?>

* [Authentication](#authentication)
<?php endif; ?>
<?php if ($openapi->components->headers): ?>

* [Headers](#headers)
<?php endif; ?>
<?php foreach ($topics_menu as $topic => $paths): ?>

* [<?php echo $topic; ?>](#topic_<?php echo Utils::slugify($topic); ?>)
<?php foreach ($paths as $path => $path_item): ?>
<?php foreach ($path_item->getOperations() as $operation): ?>

    * [<?php echo $operation->summary; ?>](#op_<?php echo Utils::slugify($operation->operationId); ?>)
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
* [Schemas](#schemas)
<?php foreach ($openapi->components->schemas as $name => $schema): ?>

    * [<?php echo $name; ?>](#schema_<?php echo Utils::slugify($name); ?>)
<?php endforeach; ?>
</nav>
<?php endif; ?>

<?php if ($openapi->components->securitySchemes): ?>

## <span id="authentication">Authentication</span>

<?php foreach ($openapi->components->securitySchemes as $security_scheme): ?>
### <?php echo $security_scheme->name; ?>

<?php
switch ($security_scheme->type) {
    case 'apiKey':
        echo 'API key';

        if ($security_scheme->in === 'header') {
            $auth_samples[$security_scheme->name] = 'API_KEY';
        }

        break;
    case 'http':
        echo 'HTTP authorization';

        if ($security_scheme->in === 'header') {
            $auth_samples[$security_scheme->name] = sprintf('%s {access-token}', $security_scheme->scheme);
        }

        break;
    case 'oauth2':
        echo 'OAuth 2';
        $auth_samples['Authorization'] = 'Bearer {access-token}';
        break;
    case 'openIdConnect':
        echo 'OpenId Connect';
        $auth_samples['Authorization'] = 'Bearer {access-token}';
        break;
}
?>


Type: *<?php echo $security_scheme->in; ?>*

<?php if ($security_scheme->scheme): ?>
Scheme: *<?php echo $security_scheme->scheme; ?>*
<?php endif; ?>

<?php if ($security_scheme->bearerFormat): ?>
Bearer: *<?php echo $security_scheme->bearerFormat; ?>*
<?php endif; ?>

<?php if ($security_scheme->openIdConnectUrl): ?>
OpenId Connect URL: *<?php echo $security_scheme->openIdConnectUrl; ?>*
<?php endif; ?>

<?php echo $security_scheme->description; ?>

<?php endforeach; ?>

<?php endif; ?>

<?php if ($openapi->components->headers): ?>

## <span id="headers">Headers</span>

<?php foreach ($openapi->components->headers as $name => $header): ?>
### <?php echo $name; ?>

<?php echo $header->description; ?>

<?php endforeach; ?>
<?php endif; ?>

<?php foreach ($topics_menu as $topic => $paths): ?>

## <a id="topic_<?php echo Utils::slugify($topic); ?>"><?php echo $topic; ?></a>

<?php foreach ($paths as $path => $path_item): ?>
<?php foreach ($path_item->getOperations() as $method => $operation): ?>

### <a id="op_<?php echo Utils::slugify($operation->operationId); ?>"><?php echo $operation->summary; ?></a>

`<?php echo strtoupper($method) .' ' . $path; ?>`

<?php if ($operation->deprecated): ?>
**DEPRECATED**
<?php endif; ?>

<?php echo $operation->description; ?>

<div class="prest-doc-code-sample">

**Code samples**

<?php
$consumers = [];
if ($operation->requestBody instanceof RequestBody) {
    foreach ($operation->requestBody->content as $content_type => $content) {
        $consumers[] = $content_type;
    }
}

if (!$consumers) {
    $consumers[] = null;
}
?>

<?php foreach ($consumers as $consumer): ?>

```http
<?php echo strtoupper($method) .' ' . $path; ?> HTTP/1.1
Host: <?php echo $server_url . PHP_EOL; ?>
<?php
$producers = [];
foreach ($operation->responses as $response) {
    foreach ($response->content as $content_type => $content) {
        $producers[] = $content_type;
    }
}

if ($producers) {
    printf("Accept: %s\n", implode(',', array_unique($producers)));
}

if ($consumer) {
    printf("Content-Type: %s\n", $consumer);
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

        printf("%s: %s\n", $parameter->name, $parameter->example);
    }
}

foreach ($auth_samples as $header => $example) {
    if ($operation_defined_auth && strtolower($header) === 'authorization') {
        continue;
    }

    printf("%s: %s\n", $header, $example);
}
?>
<?php if (isset($operation->requestBody->content[$consumer])):
        $content = $operation->requestBody->content[$consumer];
?>
<?php if ($content->example): ?>

<?php echo $content->example; ?>

<?php elseif ($content instanceof MediaType && $content->schema):
        $schema = $content->schema;
        if ($schema instanceof Reference) {
            $schema = $schema->resolve();
        }

        $include_readonly = ($method === 'GET');
?>

<?php echo json_encode(Utils::getSchemaProperties($schema, $include_readonly), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT); ?>

<?php endif; ?>
<?php endif; ?>
```
<?php endforeach; ?>
</div>

<?php if ($operation->parameters || $operation->requestBody instanceof RequestBody): ?>
**Parameters**

| Name | In | Type | Required | Description |
| ---- | -- | ---- | -------- | ----------- |
<?php foreach ($operation->parameters as $parameter):
        if ($parameter instanceof Reference) {
            $parameter = $parameter->resolve();
        }
?>
| `<?php
    echo $parameter->name;
?>` | <?php
    echo $parameter->in;
?> | <?php
    if ($parameter->schema) {
        echo $parameter->schema->type;
    }
?> | <?php
    echo ($parameter->required ? 'Yes' : 'No');
?> | <?php
    if ($parameter->description) {
        echo str_replace("\n", '<br>', $parameter->description);
    }
?> |
<?php endforeach; ?>
<?php if ($operation->requestBody instanceof RequestBody): ?>
<?php foreach ($operation->requestBody->content as $content_type => $content): ?>
<?php if ($content instanceof MediaType): ?>
| body | body | `<?php
    echo $content_type;
?>`<?php
    $ref_schema = null;
    if ($content->schema) {
        $schema = $content->schema;

        echo '<br>';

        if ($schema instanceof Reference) {
            $name = basename($schema->getReference());
            $ref_schema = $name;
            $schema = $schema->resolve();
        } else {
            $name = $schema->type;
        }

        if ($schema instanceof Schema) {
            if ($ref_schema) {
                echo '[';
            }

            echo $name;

            if ($schema->format) {
                printf(' (%s)', $schema->format);
            }

            if ($ref_schema) {
                printf('](#schema_%s)', Utils::slugify($ref_schema));
            }
        }
    }
?> | <?php
    echo ($operation->requestBody->required ? 'Yes' : 'No');
?> | <?php
    if ($operation->requestBody->description) {
        echo str_replace("\n", '<br>', $operation->requestBody->description);
    }
?> |
<?php if (!$ref_schema && $content->schema instanceof Schema): ?>
<?php foreach ($content->schema->properties as $name => $property): ?>
| » `<?php
    echo $name;
?>` | body | <?php
    echo $property->type;
?> | <?php
    echo ($property->required ? 'Yes' : 'No');
?> | <?php
    if ($property->description) {
        echo str_replace("\n", '<br>', $property->description);
    }
?> |
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php if ($operation->responses): ?>
**Responses**

| Status | Description | Schema |
| ------ | ----------- | ------ |
<?php foreach ($operation->responses as $status => $response): ?>
| <?php
    echo $status;
?> | <?php
    echo $response->description;
?> | <?php
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
            $schema = $schema->resolve();
        } else {
            $name = $schema->type;
        }

        if ($ref_schema) {
            echo '[';
        }

        if ($is_array) {
            printf('Array of %s', $name);
        } else {
            echo $name;
        }

        if ($ref_schema) {
            printf('](#schema_%s)', Utils::slugify($ref_schema));
        }
    }
?> |
<?php endforeach; ?>

<?php endif; ?>

**Example responses**

<?php endforeach; ?>
<?php endforeach; ?>

<?php endforeach; ?>

## <span id="schemas">Schemas</span>

<?php foreach ($openapi->components->schemas as $name => $schema):
        /** @var array<string, Reference|Schema> $properties */
        $properties = [];

        foreach ($schema->properties as $property_name => $property) {
            $properties[$property_name] = $property;
        }

        if ($schema->allOf) {
            foreach ($schema->allOf as $all_of_ref) {
                if ($all_of_ref instanceof Reference) {
                    $all_of_ref = $all_of_ref->resolve();
                }

                if ($all_of_ref instanceof Schema) {
                    foreach ($all_of_ref->properties as $property_name => $property) {
                        $properties[$property_name] = $property;
                    }
                }
            }
        }
?>

### <a id="schema_<?php echo Utils::slugify($name); ?>"><?php echo $name; ?></a>

**Properties**

| Name | Type | Required | Restrictions | Description |
| ---- | ---- | -------- | ------------ | ----------- |
<?php foreach ($properties as $property_name => $property):
        $is_array = false;
        $ref_schema = null;

        if ($property instanceof Schema && $property->type === 'array') {
            $items = $property->items;
            $is_array = true;
            $property = $items;
        }

        if ($property instanceof Reference) {
            $name = basename($property->getReference());
            $ref_schema = $name;
            $property = $property->resolve();
        } else {
            $name = $property->type;
        }
?>
| `<?php
    echo $property_name;
?>` | <?php
    if ($ref_schema) {
        printf('<a href="#schema_%s">', Utils::slugify($ref_schema));
    }

    if ($is_array) {
        printf('Array of %s', $name);
    } else {
        echo $name;
    }

    if ($ref_schema) {
        echo '</a>';
    }
?> | <?php
    echo ($schema->required && in_array($property_name, $schema->required, true) ? 'Yes' : 'No');
?> | <?php
    echo ($property->readOnly ? 'read-only' : 'none');

    if ($property->enum) {
        printf('<br>enum, one of `%s`', implode(', ', $property->enum));
    }
?> | <?php
    if ($property->description) {
        echo str_replace("\n", '<br>', $property->description);
    }
?> |
<?php endforeach; ?>

<?php endforeach; ?>

[Go to top](#top)
