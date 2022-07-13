<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use function array_unique;
use function basename;
use function copy;

final class AssetsRepository
{
    /** @var string[] */
    private array $javascript = [];
    /** @var string[] */
    private array $css = [];

    public function addJavaScript(string $path, BuildContext $context): void
    {
        $this->javascript[] = $this->normalize($path, $context);
    }

    public function addCss(string $path, BuildContext $context): void
    {
        $this->css[] = $this->normalize($path, $context);
    }

    /**
     * @return string[]
     */
    public function getJavaScript(): array
    {
        return array_unique($this->javascript);
    }

    /**
     * @return string[]
     */
    public function getCss(): array
    {
        return array_unique($this->css);
    }

    private function normalize(string $path, BuildContext $context): string
    {
        if ($path[0] === '/') {
            $filename = basename($path);
            @copy($path, $context->out_directory . '/' . $filename);

            return $filename;
        }

        return $path;
    }
}
