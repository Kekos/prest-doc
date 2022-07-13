<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

final class BuildContext
{
    /**
     * @param array<int, string>|string[] $exclude_paths
     */
    public function __construct(
        private string $in_directory,
        private string $out_directory,
        private string $layout,
        private string $openapi_template_directory,
        private array $exclude_paths = [],
    )
    {}

    public function getInDirectory(): string
    {
        return $this->in_directory;
    }

    public function getOutDirectory(): string
    {
        return $this->out_directory;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getOpenapiTemplateDirectory(): string
    {
        return $this->openapi_template_directory;
    }

    /**
     * @return array<int, string>|string[]
     */
    public function getExcludePaths(): array
    {
        return $this->exclude_paths;
    }
}
