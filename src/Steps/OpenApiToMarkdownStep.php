<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiEntities\TopicsRepository;
use Kekos\PrestDoc\ApiTemplateFactory;
use Kekos\PrestDoc\ApiTemplates\Contracts\Authentication;
use Kekos\PrestDoc\ApiTemplates\Contracts\FrontMatter;
use Kekos\PrestDoc\ApiTemplates\Contracts\Headers;
use Kekos\PrestDoc\ApiTemplates\Contracts\Operations;
use Kekos\PrestDoc\ApiTemplates\Contracts\Schemas;
use Kekos\PrestDoc\ApiTemplates\Contracts\TableOfContentsMenu;
use Kekos\PrestDoc\ApiTemplates\Contracts\Wrapper;
use Kekos\PrestDoc\BuildContext;
use Kekos\PrestDoc\Filesystem;
use SplFileInfo;

use function dirname;
use function implode;

use const PHP_EOL;

final class OpenApiToMarkdownStep implements BuildStep
{
    use CachesPhpTemplate;

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function processInput(SplFileInfo $current, BuildContext $context): void
    {
        if ($current->getExtension() !== 'json') {
            return;
        }

        $template_factory = new ApiTemplateFactory($context);

        $output_filepath = $this->filesystem->getOutputPathFromInput(
            file: $current,
            in_directory: $context->in_directory,
            out_directory: $context->out_directory,
            from_ext: 'json',
            to_ext: 'md',
        );

        $open_api = Reader::readFromJsonFile($current->getRealPath(), OpenApi::class, false);
        $topics = $this->generateTopicsMenu($open_api, $template_factory)->getTopics();

        $template_parts = [
            $template_factory->get(Authentication::class)->renderAuthentication($open_api),
            $template_factory->get(Headers::class)->renderHeaders($open_api),
            $template_factory->get(Operations::class)->renderOperations($open_api, $topics),
            $template_factory->get(Schemas::class)->renderSchemas($open_api),
        ];

        $content = implode(PHP_EOL, $template_parts);

        $output = $template_factory->get(FrontMatter::class)->renderFrontMatter($open_api) . PHP_EOL;
        $output .= $template_factory->get(TableOfContentsMenu::class)->renderTableOfContentsMenu($open_api, $topics) . PHP_EOL;
        $output .= $template_factory->get(Wrapper::class)->renderWrapper($open_api, $content);

        $this->filesystem->makeDirectory(dirname($output_filepath));
        $this->filesystem->putFileContents($output_filepath, $output);
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
    }

    private function generateTopicsMenu(OpenApi $open_api, ApiTemplateFactory $template_factory): TopicsRepository
    {
        $menu = $template_factory->get(TopicsRepository::class);

        foreach ($open_api->paths as $path => $path_item) {
            $menu->addPath($path_item, $path);
        }

        return $menu;
    }
}
