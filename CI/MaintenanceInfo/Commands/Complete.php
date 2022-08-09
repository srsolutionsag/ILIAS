<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Commands;

use ILIAS\CI\MaintenanceInfo\Infrastructure\Completer;
use ILIAS\CI\MaintenanceInfo\Infrastructure\Completer\Factory;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Complete extends AbstractBase
{
    public function __construct()
    {
        parent::__construct('complete');
        $this->addOption('force', '-f', InputOption::VALUE_NONE, 'Force');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input->setInteractive(true);
        if (!$input->getOption('force')) {
            $output->writeln('This features has not yet been implemented');
            return 0;
        }

        // Preload all files
        $this->file_reader->preload();

        $completer_factory = new Completer\Factory(
            $input, $output, $this->getHelper('question'), $this->collections
        );

        foreach ($this->file_reader->getExisting() as $info_file) {
            $info = $this->repository->fromInfoFile($info_file);
            $info = $completer_factory->getCompleterForItem($info)->complete($info);
            $info_file = $this->repository->toInfoFile($info, $info_file->getPath());
            $this->file_writer->writeInfoFile($info_file);
        }


        return 1;
    }
}
