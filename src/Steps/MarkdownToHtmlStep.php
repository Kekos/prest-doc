<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use Kekos\PrestDoc\BuildContext;
use Kekos\PrestDoc\Filesystem;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\Table\TableExtension;
use SplFileInfo;

use function dirname;

final class MarkdownToHtmlStep implements BuildStep
{
    use CachesPhpTemplate;

    private CommonMarkConverter $converter;

    public function __construct(
        private Filesystem $filesystem,
    )
    {
        $this->converter = new CommonMarkConverter();

        $environment = $this->converter->getEnvironment();
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new FrontMatterExtension());
    }

    public function processInput(SplFileInfo $current, BuildContext $context): void
    {
        $this->process($current, $context);
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
        if ($this->process($current, $context)) {
            $this->filesystem->removeFile($current);
        }
    }

    private function process(SplFileInfo $current, BuildContext $context): bool
    {
        if ($current->getExtension() !== 'md') {
            return false;
        }

        $output = $this->convert(
            filepath: $current->getRealPath(),
            layout_path: $context->getLayout(),
        );

        $output_filepath = $this->filesystem->getOutputPathFromInput(
            file: $current,
            in_directory: $context->getInDirectory(),
            out_directory: $context->getOutDirectory(),
            from_ext: 'md',
            to_ext: 'html',
        );

        $this->filesystem->makeDirectory(dirname($output_filepath));
        $this->filesystem->putFileContents($output_filepath, $output);

        return true;
    }

    private function convert(string $filepath, string $layout_path): string
    {
        $document = $this->converter->convert($this->filesystem->getFileContents($filepath));
        $data = [
            'content' => $document->getContent(),
        ];

        if ($document instanceof RenderedContentWithFrontMatter) {
            $data['front_matter'] = $document->getFrontMatter();
        }

        return $this
            ->getCachedTemplate($layout_path)
            ->render($data)
        ;
    }
}
