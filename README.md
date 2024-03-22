# Prest-doc

Static Site Generator for OpenAPI documentation written in PHP.

## Install

```
composer install kekos/prest-doc
```

Requires at least PHP 8.1.

## Usage

```
./bin/prest-doc <in_directory> <out_directory> <layout_file> [<config_file>]
```

* `<in_directory>` points to a directory which Prest-doc should read and convert.
* `<out_directory>` points to a directory where converted files will be written. **Please note that this directory will be deleted by Prest-doc on each run**
* `<layout_file>` is a file path to layout file. See the [`examples/`](examples) directory.
* `<config_file>` is a file path to [configuration](#configuration). *Optional*.

## Configuration

You can override the default OpenAPI Markdown templates by creating a configuration file
and supply it with your own instances of template interfaces:

```php
<?php
use Kekos\PrestDoc\Configuration;
use Kekos\PrestDoc\ApiTemplates\Contracts\SchemaTemplate;

return new Configuration(
    api_templates_class_map: [
        SchemaTemplate::class => new MyOwnImplementationOfSchemaTemplate(),
    ],
);
```

## Features

- [x] Converts OpenAPI specifications (JSON) to HTML.
- [x] Allows for own HTML layout template, written in PHP.
- [x] Allows for own CSS and JavaScript.
- [ ] Sort paths in an order logical for you and your app.
