<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

final class BuildContext
{
    public function __construct(
        public readonly string $in_directory,
        public readonly string $out_directory,
        public readonly string $layout,
        public readonly Configuration $configuration,
    ) {
    }
}
