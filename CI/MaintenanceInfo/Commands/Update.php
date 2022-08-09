<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Commands;

use ILIAS\CI\MaintenanceInfo\Infrastructure\CLIPrinter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use ILIAS\CI\MaintenanceInfo\Infrastructure\Reader;

class Update extends AbstractBase
{
    public function __construct()
    {
        parent::__construct('update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->file_reader->preload();
        $infos = [];
        foreach ($this->file_reader->getExisting() as $info_file) {
            $infos[] = $this->repository->fromInfoFile($info_file);
        }
        foreach ($infos as $info) {
            $info_file = $this->repository->toInfoFile($info);
            $this->file_writer->writeInfoFile($info_file);
            $this->file_writer->writeMarkDownFile($info_file, $info);
        }

        // Update Maintenance Markdown File
        // $this->file_writer->updateMaintenanceMarkdown($infos);


        return 1;
    }
}
