<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use Kekos\PrestDoc\ApiTemplates\Contracts\Authentication;
use Kekos\PrestDoc\ApiTemplates\Contracts\FrontMatter;
use Kekos\PrestDoc\ApiTemplates\Contracts\Headers;
use Kekos\PrestDoc\ApiTemplates\Contracts\Operations;
use Kekos\PrestDoc\ApiTemplates\Contracts\Schemas;
use Kekos\PrestDoc\ApiTemplates\Contracts\TableOfContentsMenu;
use Kekos\PrestDoc\ApiTemplates\Contracts\Wrapper;
use Kekos\PrestDoc\ApiTemplates\DefaultAuthentication;
use Kekos\PrestDoc\ApiTemplates\DefaultFrontMatter;
use Kekos\PrestDoc\ApiTemplates\DefaultHeaders;
use Kekos\PrestDoc\ApiTemplates\DefaultOperations;
use Kekos\PrestDoc\ApiTemplates\DefaultSchemas;
use Kekos\PrestDoc\ApiTemplates\DefaultTableOfContentsMenu;
use Kekos\PrestDoc\ApiTemplates\DefaultWrapper;
use Kekos\PrestDoc\Exceptions\ConfigurationException;

final class ApiTemplateFactory
{
    /** @var array<class-string, object> */
    private array $instances = [];

    public function __construct(
        BuildContext $context,
    ) {
        $this->instances[Authentication::class] = new DefaultAuthentication();
        $this->instances[FrontMatter::class] = new DefaultFrontMatter();
        $this->instances[Headers::class] = new DefaultHeaders();
        $this->instances[Operations::class] = new DefaultOperations();
        $this->instances[Schemas::class] = new DefaultSchemas();
        $this->instances[TableOfContentsMenu::class] = new DefaultTableOfContentsMenu();
        $this->instances[Wrapper::class] = new DefaultWrapper();

        $this->instances += $context->configuration->api_templates_class_map;
    }

    /**
     * @template T
     * @param class-string<T> $class_name
     * @return T
     */
    public function get(string $class_name): object
    {
        if (!isset($this->instances[$class_name])) {
            throw ConfigurationException::forClassNotFound($class_name);
        }

        return $this->instances[$class_name];
    }
}
