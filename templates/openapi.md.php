<?php declare(strict_types=1);

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kekos\PrestDoc\Utils;

/**
 * @var OpenApi $openapi
 * @var array<string, array<string, PathItem>>|PathItem[][] $topics_menu
 */
?>
---
title: <?php echo $openapi->info->title; ?> v<?php echo $openapi->info->version . PHP_EOL; ?>
---

# <?php echo $openapi->info->title; ?> v<?php echo $openapi->info->version; ?>

<?php if ($topics_menu): ?>
<nav>
<?php foreach ($topics_menu as $topic => $paths): ?>

* [<?php echo $topic; ?>](#topic_<?php echo Utils::slugify($topic); ?>)
<?php foreach ($paths as $path => $path_item): ?>
<?php foreach ($path_item->getOperations() as $method => $operation): ?>

    * [<?php echo $operation->summary; ?>](#op_<?php echo Utils::slugify($operation->summary); ?>)
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>
<?php endif; ?>

<?php if ($openapi->components->securitySchemes): ?>

## Authentication

<?php foreach ($openapi->components->securitySchemes as $security_scheme): ?>
### <?php echo $security_scheme->name; ?>

<?php
switch ($security_scheme->type) {
    case 'apiKey':
        echo 'API key';
        break;
    case 'http':
        echo 'HTTP authorization';
        break;
    case 'oauth2':
        echo 'OAuth 2';
        break;
    case 'openIdConnect':
        echo 'OpenId Connect';
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

## Headers

<?php foreach ($openapi->components->headers as $name => $header): ?>
### <?php echo $name; ?>

<?php echo $header->description; ?>

<?php endforeach; ?>
<?php endif; ?>

<?php foreach ($topics_menu as $topic => $paths): ?>

## <a id="topic_<?php echo Utils::slugify($topic); ?>"><?php echo $topic; ?></a>

<?php foreach ($paths as $path => $path_item): ?>
<?php foreach ($path_item->getOperations() as $method => $operation): ?>

### <a id="op_<?php echo Utils::slugify($operation->summary); ?>"><?php echo $operation->summary; ?></a>

`<?php echo strtoupper($method) .' ' . $path; ?>`

<?php if ($operation->deprecated): ?>
**DEPRECATED**
<?php endif; ?>

<?php echo $operation->description; ?>

<?php if ($operation->parameters): ?>
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

<?php endforeach; ?>
<?php endforeach; ?>

<?php endforeach; ?>

## Schemas

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
