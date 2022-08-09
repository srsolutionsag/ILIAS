<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Infrastructure\Completer;

use ILIAS\CI\MaintenanceInfo\Inventory\Collections\ComponentCollection;
use ILIAS\CI\MaintenanceInfo\Inventory\Collections\PersonCollection;
use ILIAS\CI\MaintenanceInfo\Inventory\Component;
use ILIAS\CI\MaintenanceInfo\Inventory\Info;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Role;
use ILIAS\CI\MaintenanceInfo\Inventory\SerializableInventoryItem;
use ILIAS\CI\MaintenanceInfo\Storage\InfoFile;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

interface Completer
{
    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $question, Factory $factory);

    public function setInputOptions(array $options): void;

    public function complete(SerializableInventoryItem $item): SerializableInventoryItem;
}
