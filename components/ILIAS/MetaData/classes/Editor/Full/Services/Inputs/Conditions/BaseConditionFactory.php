<?php

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

declare(strict_types=1);

namespace ILIAS\MetaData\Editor\Full\Services\Inputs\Conditions;

use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Editor\Full\Services\Inputs\WithoutConditions\FactoryWithoutConditionTypesService;
use ILIAS\UI\Component\Input\Field\Factory as UIFactory;
use ILIAS\MetaData\Repository\Validation\Dictionary\DictionaryInterface as ConstraintDictionary;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Vocabularies\Slots\Identifier as SlotIdentifier;
use ILIAS\MetaData\Editor\Full\Services\Inputs\WithoutConditions\InputHelper;

abstract class BaseConditionFactory
{
    use InputHelper;

    protected UIFactory $ui_factory;
    protected PresenterInterface $presenter;
    protected ConstraintDictionary $constraint_dictionary;
    protected FactoryWithoutConditionTypesService $types;

    public function __construct(
        UIFactory $ui_factory,
        PresenterInterface $presenter,
        ConstraintDictionary $constraint_dictionary,
        FactoryWithoutConditionTypesService $types
    ) {
        $this->ui_factory = $ui_factory;
        $this->presenter = $presenter;
        $this->constraint_dictionary = $constraint_dictionary;
        $this->types = $types;
    }

    abstract public function getConditionInput(
        ElementInterface $element,
        ElementInterface $context_element,
        ElementInterface $conditional_element
    ): FormInput;

    protected function getInputInCondition(
        ElementInterface $element,
        ElementInterface $context_element,
        SlotIdentifier $conditional_slot
    ): FormInput {
        $input_factory = $this->types->factory($element->getDefinition()->dataType());
        return $input_factory->getInputInCondition(
            $element,
            $context_element,
            $conditional_slot
        );
    }
}
