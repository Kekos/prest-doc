<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kekos\PrestDoc\ApiTaggedTopicsRepository;
use Kekos\PrestDoc\BuildContext;
use Kekos\PrestDoc\Filesystem;
use SplFileInfo;

use function dirname;

final class OpenApiToMarkdownStep implements BuildStep
{
    use CachesPhpTemplate;

    public function __construct(
        private Filesystem $filesystem,
    )
    {}

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

        $template_path = $context->getOpenapiTemplateDirectory() . '/openapi.md.php';
        $openapi = Reader::readFromJsonFile($current->getRealPath(), OpenApi::class, false);
        $output = $this
            ->getCachedTemplate($template_path)
            ->render([
                'openapi' => $openapi,
                'topics_menu' => $this->generateTopicsMenu($openapi)->getTopics(),
            ])
        ;

        $this->filesystem->makeDirectory(dirname($output_filepath));
        $this->filesystem->putFileContents($output_filepath, $output);
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
    }

    private function generateTopicsMenu(OpenApi $open_api): ApiTaggedTopicsRepository
    {
        $menu = new ApiTaggedTopicsRepository();

        foreach ($open_api->paths as $path => $path_item) {
            $menu->add($path_item, $path);
        }

        return $menu;
    }
}
