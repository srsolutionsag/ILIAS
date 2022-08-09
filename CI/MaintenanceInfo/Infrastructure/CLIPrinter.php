<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Infrastructure;

use ILIAS\CI\MaintenanceInfo\Inventory\Info;
use ILIAS\CI\MaintenanceInfo\Maintenance\Component\Component;
use ILIAS\CI\MaintenanceInfo\Maintenance\Model\Model;
use ILIAS\CI\MaintenanceInfo\Maintenance\Person\Person;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class CLIPrinter implements Printer
{
    protected OutputInterface $output;
    protected PrimaryPersonResolver $person_resolver;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->person_resolver = new PrimaryPersonResolver();
    }

    /**
     * @param Info[] $infos
     */
    public function printInfos(array $infos): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Directory', 'Component', 'Primary Contact']);
        foreach ($infos as $info) {
            $table->addRow([
                $info->getPath()->getDirectory(),
                $info->getComponent()->getTitle(),
                $this->person_resolver->resolve($info)->getDocuUserName()
            ]);
        }
        $table->render();
    }

    public function printModel(Model $model): void
    {
    }

    /**
     * @param Model[] $models
     */
    public function printModels(array $models): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Component', 'Path', 'Model', 'Contact']);
        foreach ($models as $model) {
            $table->addRow([
                $model->getComponent()->getComponentName(),
                $model->getComponent()->getPath(),
                $model->getModelName(),
                $model->getPrimaryContact()->getDocuUsername()
            ]);
        }
        $table->render();
    }

    public function printPerson(Person $person): void
    {
    }

    /**
     * @param \ILIAS\CI\MaintenanceInfo\Maintenance\Person\Person[] $persons
     */
    public function printPersons(array $persons): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Username']);
        foreach ($persons as $person) {
            $table->addRow([
                $person->getId(),
                $person->getDocuUsername(),
            ]);
        }
        $table->render();
    }

    /**
     * @param Component[] $components
     */
    public function printComponents(array $components): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Component', 'Path']);
        foreach ($components as $component) {
            $table->addRow([
                $component->getComponentName(),
                $component->getPath(),
            ]);
        }
        $table->render();
    }
}
