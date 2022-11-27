<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use Kekos\PrestDoc\Exceptions\TemplateRenderException;

use function call_user_func;
use function extract;
use function is_string;

use function ob_get_clean;
use function ob_start;

use const EXTR_OVERWRITE;

final class PhpTemplate
{
    public function __construct(
        private readonly string $filepath,
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(array $variables = []): string
    {
        ob_start();

        call_user_func(
            function () use ($variables) {
                extract($variables, EXTR_OVERWRITE);

                return require $this->filepath;
            },
        );

        $output = ob_get_clean();

        if (!is_string($output)) {
            throw TemplateRenderException::forNotAString($this->filepath);
        }

        return $output;
    }
}
