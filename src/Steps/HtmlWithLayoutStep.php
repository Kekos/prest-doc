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

use function is_file;

final class HtmlWithLayoutStep implements BuildStep
{
    private FrontMatterParser $front_matter_parser;
    private ?PhpTemplate $cached_template = null;

    public function __construct(
        private Filesystem $filesystem,
        private AssetsRepository $assets,
    )
    {
        $this->front_matter_parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    }

    public function processInput(SplFileInfo $current, BuildContext $context): void
    {
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
        if ($current->getExtension() !== 'html') {
            return;
        }

        $layout_template = $this->getLayoutTemplate($context);
        $html_content = $this->filesystem->getFileContents($current->getRealPath());

        $result = $this->front_matter_parser->parse($html_content);

        $output = $layout_template->render([
            'assets' => $this->assets,
            'front_matter' => $result->getFrontMatter(),
            'content' => $result->getContent(),
        ]);

        $this->filesystem->putFileContents(
            $current->getRealPath(),
            $output,
        );
    }

    private function getLayoutTemplate(BuildContext $context): PhpTemplate
    {
        if ($this->cached_template) {
            return $this->cached_template;
        }

        if (!is_file($context->getLayout())) {
            throw FaultyContextException::forTemplate($context->getLayout());
        }

        return $this->cached_template = new PhpTemplate($context->getLayout());
    }
}
