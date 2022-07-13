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

### API templates

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

### Topics menu

Prest-doc can be configured with how it should create the main API navigation, the "topics menu". Two implementations
are included, but you can build your own by implementing the interface `\Kekos\PrestDoc\ApiEntities\TopicsRepository`.
Use the `Configuration` class´ `api_templates_class_map` property to configure this.

#### `TaggedTopicsRepository` (default)

All path operations must have a tag prefixed with `topic-`. That tag will be used to group operations together under the
name of tag, excluding `topic-`.

#### `SingleTopicRepository`

All paths are grouped to a single topic *"Operations"*.

## Features

- [x] Converts OpenAPI specifications (JSON) to HTML.
- [x] Allows for own HTML layout template, written in PHP.
- [x] Allows for own CSS and JavaScript.
- [ ] Sort paths (topics) in an order logical for you and your app.

## Bugs and improvements

Report bugs in GitHub issues or feel free to make a pull request :-)

## License

MIT
