<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\CI\MaintenanceInfo\Commands;

use ILIAS\CI\MaintenanceInfo\Infrastructure\Reader;
use ILIAS\CI\MaintenanceInfo\Inventory\Collections\Collections;
use ILIAS\CI\MaintenanceInfo\Inventory\Repository;
use ILIAS\CI\MaintenanceInfo\Storage\BaseReader;
use ILIAS\CI\MaintenanceInfo\Storage\BaseWriter;
use Symfony\Component\Console\Command\Command;

abstract class AbstractBase extends Command
{
    protected BaseReader $file_reader;
    protected Repository $repository;
    protected BaseWriter $file_writer;
    protected Collections $collections;

    public function __construct(string $command_name)
    {
        parent::__construct($command_name);
        $this->file_reader = new BaseReader();
        $this->file_writer = new BaseWriter();
        $this->collections = new Collections();
        $this->repository = new Repository($this->collections);
    }
}
