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

class InfoCompleter extends GeneralCompleter implements Completer
{
    protected function buildQuestion(string $key, SerializableInventoryItem $in_item): string
    {
        /** @var $in_item Person */
        return "Please fill in the missing value for $key for Docu-User-ID ({$in_item->getDocuUserID()})";
    }

    protected function isMandatory(string $key, SerializableInventoryItem $in_item): bool
    {
        return $key === 'check';
    }


}
