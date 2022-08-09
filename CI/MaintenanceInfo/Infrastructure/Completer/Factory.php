<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Infrastructure\Completer;

use ILIAS\CI\MaintenanceInfo\Inventory\Collections\Collections;
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

class Factory
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected QuestionHelper $question;
    protected Collections $collections;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $question
     */
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $question,
        Collections $collections
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->question = $question;
        $this->collections = $collections;
    }


    public function getCompleterForItem(SerializableInventoryItem $item): Completer
    {
        switch (get_class($item)) {
            case Component::class:
                $completer = new ComponentCompleter($this->input, $this->output, $this->question, $this);
                break;
            case Person::class:
                $completer = new PersonCompleter($this->input, $this->output, $this->question, $this);
                break;
//            case Role::class:

//                return new RoleCompleter($this->input, $this->output, $this->question);
            case Info::class:
                $completer = new InfoCompleter($this->input, $this->output, $this->question, $this);
                break;
            default:
                $completer = new GeneralCompleter($this->input, $this->output, $this->question, $this);
                break;
        }


        return $completer;
    }

}
