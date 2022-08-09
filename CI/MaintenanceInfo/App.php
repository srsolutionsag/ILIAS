<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo;

use Symfony\Component\Console\Application;
use ILIAS\CI\MaintenanceInfo\Commands\AbstractBase;

/**
 * Class App
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class App extends Application
{
    const NAME = 'Maintenance Info Generator';

    public function __construct(AbstractBase ...$commands)
    {
        parent::__construct(self::NAME);
        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
