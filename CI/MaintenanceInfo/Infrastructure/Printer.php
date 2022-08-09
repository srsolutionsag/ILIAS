<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Infrastructure;

use ILIAS\CI\MaintenanceInfo\Maintenance\Model\Model;
use ILIAS\CI\MaintenanceInfo\Maintenance\Person\Person;

interface Printer
{
    public function printModel(Model $model) : void;

    /**
     * @param Model[] $models
     * @return void
     */
    public function printModels(array $models) : void;

    public function printPerson(Person $person) : void;
}
