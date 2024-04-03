<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use FilesystemIterator;
use Kekos\PrestDoc\Exceptions\FilesystemException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function ltrim;
use function mkdir;
use function rmdir;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strrpos;
use function substr;
use function unlink;

final class Filesystem
{
    public function getRelativePath(SplFileInfo|string $file, string $directory): string
    {
        $file_real_path = $file;
        if ($file instanceof SplFileInfo) {
            $file_real_path = $file->getRealPath();
        }

        if (!str_starts_with($file_real_path, $directory)) {
            return $file_real_path;
        }

        return substr($file_real_path, strlen($directory) + 1);
    }

    public function getOutputPathFromInput(
        SplFileInfo $file,
        string $in_directory,
        string $out_directory,
        string $from_ext,
        string $to_ext,
    ): string {
        $relative = dirname($this->getRelativePath($file, $in_directory));

        if ($relative === '.') {
            $relative = '';
        }

        if ($relative !== '') {
            $relative .= '/';
        }

        return sprintf(
            '%s/%s%s',
            $out_directory,
            $relative,
            $this->changeExtension($file->getFilename(), $from_ext, $to_ext),
        );
    }

    public function getFileContents(string $filepath): string
    {
        $contents = @file_get_contents($filepath);
        if ($contents === false) {
            throw FilesystemException::forMethod('getFileContents', $filepath);
        }

        return $contents;
    }

    public function putFileContents(string $filepath, string $data): int
    {
        $bytes = @file_put_contents($filepath, $data);
        if ($bytes === false) {
            throw FilesystemException::forMethod('putFileContents', $filepath);
        }

        return $bytes;
    }

    public function makeDirectory(string $filepath): void
    {
        if (
            !is_dir($filepath)
            && !mkdir($filepath, 0770, true)
            && !is_dir($filepath)
        ) {
            throw FilesystemException::forMethod('mkdir', $filepath);
        }
    }

    public function changeExtension(string $filename, string $from_ext, string $to_ext): string
    {
        $from_ext = sprintf('.%s', ltrim($from_ext, '.'));
        $to_ext = sprintf('.%s', ltrim($to_ext, '.'));

        $rpos = strrpos($filename, $from_ext);
        if (!$rpos) {
            throw new FilesystemException(
                sprintf(
                    'The extension "%s" could not be found in "%s"',
                    $from_ext,
                    $filename,
                )
            );
        }

        return substr($filename, 0, $rpos) . $to_ext;
    }

    public function remove(SplFileInfo $file): void
    {
        if (!$file->isDir()) {
            throw new FilesystemException('Not a directory');
        }

        $directory = new RecursiveDirectoryIterator(
            $file->getPathname(),
            FilesystemIterator::SKIP_DOTS,
        );
        $iterator = new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::LEAVES_ONLY | RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $it_file */
        foreach ($iterator as $it_file) {
            if ($it_file->isFile()) {
                $this->removeFile($it_file);
            } else {
                $this->removeDirectory($it_file);
            }
        }

        $this->removeDirectory($file);
    }

    public function removeFile(SplFileInfo $file): void
    {
        if (!@unlink($file->getPathname())) {
            throw FilesystemException::forMethod('unlink', $file->getPathname());
        }
    }

    public function removeDirectory(SplFileInfo $file): void
    {
        if (!@rmdir($file->getPathname())) {
            throw FilesystemException::forMethod('rmdir', $file->getPathname());
        }
    }
}
