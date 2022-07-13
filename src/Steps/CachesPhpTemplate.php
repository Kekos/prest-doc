<?php declare(strict_types=1);

namespace Kekos\PrestDoc\Steps;

use Kekos\PrestDoc\Exceptions\FaultyContextException;
use Kekos\PrestDoc\PhpTemplate;

use function is_file;

trait CachesPhpTemplate
{
    private ?PhpTemplate $cached_template = null;

    final protected function getCachedTemplate(string $template_path): PhpTemplate
    {
        if ($this->cached_template) {
            return $this->cached_template;
        }

        if (!is_file($template_path)) {
            throw FaultyContextException::forTemplate($template_path);
        }

        return $this->cached_template = new PhpTemplate($template_path);
    }
}
