<?php

declare(strict_types=1);
$executed_in_directory = getcwd();
chdir(__DIR__ . "/../..");
require_once("./libs/composer/vendor/autoload.php");

use ILIAS\CI\MaintenanceInfo\App;
use ILIAS\CI\MaintenanceInfo\Commands\Complete;
use ILIAS\CI\MaintenanceInfo\Commands\Overview;
use Symfony\Component\Console\Command\Command;
use ILIAS\CI\MaintenanceInfo\Commands\Update;

$app = new App(
    new Overview(),
    new Update(),
    new Complete()
);
$app->run();
