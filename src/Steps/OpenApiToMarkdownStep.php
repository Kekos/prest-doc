<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiEntities\TaggedTopicsRepository;
use Kekos\PrestDoc\ApiTemplates\DefaultAuthentication;
use Kekos\PrestDoc\ApiTemplates\DefaultFrontMatter;
use Kekos\PrestDoc\ApiTemplates\DefaultHeaders;
use Kekos\PrestDoc\ApiTemplates\DefaultOperations;
use Kekos\PrestDoc\ApiTemplates\DefaultSchemas;
use Kekos\PrestDoc\ApiTemplates\DefaultTableOfContentsMenu;
use Kekos\PrestDoc\ApiTemplates\DefaultWrapper;
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

        $output_filepath = $this->filesystem->getOutputPathFromInput(
            file: $current,
            in_directory: $context->getInDirectory(),
            out_directory: $context->getOutDirectory(),
            from_ext: 'json',
            to_ext: 'md',
        );

        $open_api = Reader::readFromJsonFile($current->getRealPath(), OpenApi::class, false);
        $topics = $this->generateTopicsMenu($open_api)->getTopics();

        $template_parts = [
            (new DefaultAuthentication())->getAuthentication($open_api),
            (new DefaultHeaders())->getHeaders($open_api),
            (new DefaultOperations())->getOperations($open_api, $topics),
            (new DefaultSchemas())->getSchemas($open_api),
        ];

        $content = implode(PHP_EOL, $template_parts);

        $output = (new DefaultFrontMatter())->getFrontMatter($open_api) . PHP_EOL;
        $output .= (new DefaultTableOfContentsMenu())->getTableOfContentsMenu($open_api, $topics) . PHP_EOL;
        $output .= (new DefaultWrapper())->getWrapper($open_api, $content);

        $this->filesystem->makeDirectory(dirname($output_filepath));
        $this->filesystem->putFileContents($output_filepath, $output);
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
    }

    private function generateTopicsMenu(OpenApi $open_api): TaggedTopicsRepository
    {
        $menu = new TaggedTopicsRepository();

        foreach ($open_api->paths as $path => $path_item) {
            $menu->addPath($path_item, $path);
        }

        return $menu;
    }
}
