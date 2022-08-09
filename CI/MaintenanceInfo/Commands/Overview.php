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

use ILIAS\CI\MaintenanceInfo\Infrastructure\CLIPrinter;
use ILIAS\CI\MaintenanceInfo\Inventory\DemoInfo;
use ILIAS\CI\MaintenanceInfo\Inventory\Repository;
use ILIAS\CI\MaintenanceInfo\Storage\InfoFile;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Overview extends AbstractBase
{
    public function __construct()
    {
        parent::__construct('overview');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infos = [];
        foreach ($this->file_reader->getExisting() as $info_file) {
            $infos[] = $this->repository->fromInfoFile($info_file);
        }
        $printer = new CLIPrinter($output);
        $printer->printInfos($infos);

        $infos = [];
        foreach ($this->file_reader->getMissing() as $info_file) {
            $infos[] = $this->repository->fromInfoFile($info_file);
        }
        $printer = new CLIPrinter($output);
        $printer->printInfos($infos);

        return 1;
    }
}
