<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use Kekos\PrestDoc\AssetsRepository;
use Kekos\PrestDoc\BuildContext;
use Kekos\PrestDoc\Exceptions\FaultyContextException;
use Kekos\PrestDoc\Filesystem;
use Kekos\PrestDoc\PhpTemplate;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use SplFileInfo;

use function dirname;
use function is_file;

final class HtmlWithLayoutStep implements BuildStep
{
    private FrontMatterParser $front_matter_parser;
    private ?PhpTemplate $cached_template = null;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AssetsRepository $assets,
    ) {
        $this->front_matter_parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    }

    public function processInput(SplFileInfo $current, BuildContext $context): void
    {
        if ($current->getExtension() !== 'html') {
            return;
        }

        $output_filepath = $this->filesystem->getOutputPathFromInput(
            file: $current,
            in_directory: $context->in_directory,
            out_directory: $context->out_directory,
            from_ext: 'html',
            to_ext: 'html',
        );

        $layout_template = $this->getLayoutTemplate($context);
        $html_content = $this->filesystem->getFileContents($current->getRealPath());

        $result = $this->front_matter_parser->parse($html_content);

        $output = $layout_template->render([
            'assets' => $this->assets,
            'front_matter' => $result->getFrontMatter(),
            'content' => $result->getContent(),
        ]);

        $this->filesystem->makeDirectory(dirname($output_filepath));
        $this->filesystem->putFileContents(
            $output_filepath,
            $output,
        );
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
    }

    private function getLayoutTemplate(BuildContext $context): PhpTemplate
    {
        if ($this->cached_template) {
            return $this->cached_template;
        }

        if (!is_file($context->layout)) {
            throw FaultyContextException::forTemplate($context->layout);
        }

        return $this->cached_template = new PhpTemplate($context->layout);
    }
}
