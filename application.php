#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use PhpParallelProcessing\Commands\MainCommand;
use Symfony\Component\Console\Application;

$command = new MainCommand();
$application = new Application();
$application->add($command);
$application->setDefaultCommand($command->getName(), true);

$application->run();