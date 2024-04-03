<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use Kekos\PrestDoc\AssetsRepository;
use Kekos\PrestDoc\BuildContext;
use Kekos\PrestDoc\Filesystem;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\Table\TableExtension;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer as HighlighterFencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer as HighlighterIndentedCodeRenderer;
use SplFileInfo;

use function dirname;
use function HighlightUtilities\getStyleSheetPath;

final class MarkdownToHtmlStep implements BuildStep
{
    use CachesPhpTemplate;

    private const HIGHLIGHT_LANGUAGES = [
        'http',
        'json',
    ];

    private CommonMarkConverter $converter;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AssetsRepository $assets,
    ) {
        $this->converter = new CommonMarkConverter();

        $environment = $this->converter->getEnvironment();
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new FrontMatterExtension());
        $environment->addRenderer(FencedCode::class, new HighlighterFencedCodeRenderer(self::HIGHLIGHT_LANGUAGES));
        $environment->addRenderer(IndentedCode::class, new HighlighterIndentedCodeRenderer(self::HIGHLIGHT_LANGUAGES));
    }

    public function processInput(SplFileInfo $current, BuildContext $context): void
    {
        $this->process($current, $context, $context->in_directory);
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
        if ($this->process($current, $context, $context->out_directory)) {
            $this->filesystem->removeFile($current);
        }
    }

    private function process(SplFileInfo $current, BuildContext $context, string $relative_dir): bool
    {
        if ($current->getExtension() !== 'md') {
            return false;
        }

        $this->assets->addCss(getStyleSheetPath('a11y-light'), $context);

        $output_filepath = $this->filesystem->getOutputPathFromInput(
            file: $current,
            in_directory: $relative_dir,
            out_directory: $context->out_directory,
            from_ext: 'md',
            to_ext: 'html',
        );

        $relative_in_file = $this->filesystem->getRelativePath($current, $relative_dir);
        $relative_out_file = $this->filesystem->getRelativePath($output_filepath, $context->out_directory);

        $output = $this->convert(
            filepath: $current->getRealPath(),
            layout_path: $context->layout,
            relative_in_file: $relative_in_file,
            relative_out_file: $relative_out_file,
        );

        $this->filesystem->makeDirectory(dirname($output_filepath));
        $this->filesystem->putFileContents($output_filepath, $output);

        return true;
    }

    private function convert(string $filepath, string $layout_path, string $relative_in_file, string $relative_out_file): string
    {
        $document = $this->converter->convert($this->filesystem->getFileContents($filepath));
        $data = [
            'assets' => $this->assets,
            'content' => $document->getContent(),
            'in_file' => $relative_in_file,
            'out_file' => $relative_out_file,
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
