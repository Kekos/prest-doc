<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use Kekos\PrestDoc\BuildContext;
use SplFileInfo;

interface BuildStep
{
    public function processInput(SplFileInfo $current, BuildContext $context): void;

    public function processOutput(SplFileInfo $current, BuildContext $context): void;
}
