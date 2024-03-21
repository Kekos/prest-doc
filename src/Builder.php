<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use FilesystemIterator;
use Kekos\PrestDoc\Exceptions\FaultyContextException;
use Kekos\PrestDoc\Steps\BuildStep;
use Kekos\PrestDoc\Steps\CopyStaticStep;
use Kekos\PrestDoc\Steps\HtmlWithLayoutStep;
use Kekos\PrestDoc\Steps\MarkdownToHtmlStep;
use Kekos\PrestDoc\Steps\OpenApiToMarkdownStep;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function is_dir;

final class Builder
{
    /** @var array<int, BuildStep>|BuildStep[] */
    private array $steps = [];
    private Filesystem $filesystem;

    public function __construct()
    {
        $assets = new AssetsRepository();
        $this->filesystem = new Filesystem();

        $this->steps[] = new OpenApiToMarkdownStep($this->filesystem);
        $this->steps[] = new HtmlWithLayoutStep($this->filesystem, $assets);
        $this->steps[] = new MarkdownToHtmlStep($this->filesystem, $assets);
        $this->steps[] = new CopyStaticStep($this->filesystem);
    }

    public function build(BuildContext $context): void
    {
        if (!is_dir($context->in_directory)) {
            throw FaultyContextException::forInputDirectory($context->in_directory);
        }

        if (is_dir($context->out_directory)) {
            $this->cleanOutputDirectory($context->out_directory);
        }

        $this->runForInput($context);
        $this->runForOutput($context);
    }

    /**
     * @return RecursiveIteratorIterator<RecursiveDirectoryIterator>
     */
    private function getIterator(string $path): RecursiveIteratorIterator
    {
        $directory = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS,
        );

        return new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::LEAVES_ONLY | RecursiveIteratorIterator::SELF_FIRST,
        );
    }

    private function runForInput(BuildContext $context): void
    {
        $iterator = $this->getIterator($context->in_directory);

        foreach ($this->steps as $step) {
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $step->processInput($file, $context);
            }
        }
    }

    private function runForOutput(BuildContext $context): void
    {
        $iterator = $this->getIterator($context->out_directory);

        foreach ($this->steps as $step) {
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $step->processOutput($file, $context);
            }
        }
    }

    private function cleanOutputDirectory(string $path): void
    {
        $this->filesystem->remove(new SplFileInfo($path));
    }
}
