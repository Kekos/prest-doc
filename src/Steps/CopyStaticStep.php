<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use Kekos\PrestDoc\BuildContext;
use Kekos\PrestDoc\Exceptions\FilesystemException;
use Kekos\PrestDoc\Filesystem;
use SplFileInfo;

use function copy;
use function dirname;
use function is_dir;
use function sprintf;
use function str_starts_with;

final class CopyStaticStep implements BuildStep
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function processInput(SplFileInfo $current, BuildContext $context): void
    {
        $relative = $this->filesystem->getRelativePath($current, $context->in_directory);
        if (!str_starts_with($relative, 'static/')) {
            return;
        }

        $destination_path = $this->filesystem->getOutputPathFromInput(
            file: $current,
            in_directory: $context->in_directory,
            out_directory: $context->out_directory,
            from_ext: $current->getExtension(),
            to_ext: $current->getExtension(),
        );

        $destination_dir_path = dirname($destination_path);
        if (!is_dir($destination_path)) {
            $this->filesystem->makeDirectory($destination_dir_path);
        }

        $result = @copy($current->getRealPath(), $destination_path);

        if (!$result) {
            throw new FilesystemException(
                sprintf('Failed copy from "%s" to "%s"', $current->getRealPath(), $destination_path)
            );
        }
    }

    public function processOutput(SplFileInfo $current, BuildContext $context): void
    {
    }
}
