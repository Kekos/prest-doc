<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use Throwable;

use function dirname;
use function printf;

final class ConsoleApplication
{
    public const VERSION = '0.0.0';

    /**
     * @param array<int, string>|string[] $arguments
     */
    public function run(array $arguments): int
    {
        if (!isset($arguments[1], $arguments[2], $arguments[3])) {
            return $this->usage();
        }

        $builder = new Builder();
        $build_context = new BuildContext(
            in_directory: $arguments[1],
            out_directory: $arguments[2],
            layout: $arguments[3],
            openapi_template_directory: $arguments[4] ?? dirname(__DIR__) . '/templates',
        );

        try {
            $builder->build($build_context);
        } catch (Throwable $ex) {
            $this->error($ex);

            return $ex->getCode();
        }

        return 0;
    }

    private function usage(): int
    {
        printf("PrestDoc version %s\n\n", self::VERSION);
        echo "Usage:\n\n";
        echo "\tprest-doc <in_directory> <out_directory> <layout_file> [<templates_directory>]\n\n";

        return 0;
    }

    private function error(Throwable $ex): void
    {
        printf("Error: %s\n", $ex->getMessage());
    }
}
