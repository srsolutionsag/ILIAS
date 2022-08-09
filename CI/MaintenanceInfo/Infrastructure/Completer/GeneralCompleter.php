<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Infrastructure\Completer;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItemCollection;
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

class GeneralCompleter implements Completer
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected QuestionHelper $question;
    protected Factory $factory;
    protected array $options = [];


    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $question,
        Factory $factory
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->question = $question;
        $this->factory = $factory;
    }

    public function setInputOptions(array $options): void
    {
        $this->options = $options;
    }


    protected function buildQuestion(string $key, SerializableInventoryItem $in_item): string
    {
        return "Please fill in the missing value for $key in ({$in_item->jsonSerialize()})";
    }

    protected function isMandatory(string $key, SerializableInventoryItem $in_item): bool
    {
        return false;
    }

    protected function completeItem(SerializableInventoryItem $value): SerializableInventoryItem
    {
        return $this->factory->getCompleterForItem($value)->complete($value);
    }


    protected function completeItemCollection(AbstractInventoryItemCollection $c): AbstractInventoryItemCollection
    {
        $collection = [];
        $c = $this->factory->getCompleterForItem($c)->complete($c);
        foreach ($c->get() as $item) {
            $collection[] = $this->factory->getCompleterForItem($item)->complete($item);
        }
        $c->set($collection);
        return $c;
    }

    public function complete(SerializableInventoryItem $item): SerializableInventoryItem
    {
        $reflection = new \ReflectionClass($item);
        $data = $item->jsonSerialize();

        foreach ($data as $key => $value) {
            if ($value instanceof AbstractInventoryItemCollection) {
//                $data[$key] = $this->completeItemCollection($value);
                continue;
            }
            if ($value instanceof SerializableInventoryItem) {
//                $data[$key] = $this->completeItem($value);
                continue;
            }
            if (!$this->isMandatory((string)$key, $item)) {
                continue;
            }
            if ($value !== null) {
                continue;
            }
            $property = $reflection->getProperty($key);
            if ($property->hasType()
                && $property->getType()->getName() !== 'string') { // currently only string is supported
                continue;
            }


            $new_value = $this->askSelection($this->buildQuestion($key, $item));
            $data[$key] = $new_value;
        }
        $item->jsonDeserialize($data);

        return $item;
    }

    protected function skip(string $question): bool
    {
        $question = new Question($question . ' (Y|n)' . PHP_EOL, 'y');
        $question->setValidator(function ($answer) {
            if (in_array($answer, ['y', 'n'])) {
                return $answer;
            }
            throw new \RuntimeException('Please answer with y or n');
        });
        $answer = $this->question->ask($this->input, $this->output, $question);

        return $answer === 'y';
    }


    protected function askSelection(string $question): ?string
    {
        $question = new Question(
            $question . PHP_EOL
        );
        $question->setAutocompleterValues($this->options);
        $question->setValidator(function ($input) {
            return $input;
        });
        $answer = $this->question->ask($this->input, $this->output, $question);

        return is_string($answer) ? $answer : null;
    }


}
