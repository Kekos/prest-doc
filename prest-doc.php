#!/usr/bin/env php
<?php declare(strict_types=1);

use Kekos\PrestDoc\ConsoleApplication;

require __DIR__ . '/vendor/autoload.php';

$application = new ConsoleApplication();
exit($application->run($argv));
