<?php declare(strict_types=1);

namespace Kekos\PrestDoc;

use Throwable;

use function getcwd;
use function printf;
use function str_starts_with;

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

        $arguments[1] = $this->makeAbsolutePath($arguments[1]);
        $arguments[2] = $this->makeAbsolutePath($arguments[2]);
        $arguments[3] = $this->makeAbsolutePath($arguments[3]);

        if (isset($arguments[4])) {
            $config = ConfigurationLoader::fromPath($this->makeAbsolutePath($arguments[4]));
        } else {
            $config = new Configuration();
        }

        $builder = new Builder();
        $build_context = new BuildContext(
            in_directory: $arguments[1],
            out_directory: $arguments[2],
            layout: $arguments[3],
            configuration: $config,
        );

        try {
            $builder->build($build_context);
        } catch (Throwable $ex) {
            $this->error($ex);

            return $ex->getCode();
        }

        return 0;
    }

    private function makeAbsolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd() . '/' . $path;
    }

    private function usage(): int
    {
        printf("PrestDoc version %s\n\n", self::VERSION);
        echo "Usage:\n\n";
        echo "\tprest-doc <in_directory> <out_directory> <layout_file> [<config_file>]\n\n";

        return 0;
    }

    private function error(Throwable $ex): void
    {
        printf("Error: %s\n", $ex->getMessage());
    }
}
