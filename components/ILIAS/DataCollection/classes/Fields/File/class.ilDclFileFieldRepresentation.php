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

use ILIAS\DataCollection\Validation\File\FileValidation;
use ILIAS\DataCollection\Validation\File\FileValidationCollection;
use ILIAS\FileUpload\Processor\PreProcessorManagerImpl;
use ILIAS\FileUpload\Processor\PreProcessorManager;

class ilDclFileFieldRepresentation extends ilDclBaseFieldRepresentation
{
    private FileValidationCollection $validations;
    private PreProcessorManager $pre_processor_manager;

    public function __construct(ilDclBaseFieldModel $field)
    {
        parent::__construct($field);
        global $DIC;
        $this->validations = $DIC[FileValidationCollection::class];
        $this->pre_processor_manager = $DIC['upload.processor-manager'];
    }

    public function getInputField(
        ilPropertyFormGUI $form,
        ?int $record_id = null
    ): ilFileInputGUI {
        // Register PreProcessor
        $pre_processors = $this->validations->getAllPreProcessors(
            function (FileValidation $validation): mixed {
                if (($config_key = $validation->getConfigKey()) === null) {
                    return null;
                }
                return $this->getField()->getProperty($config_key);
            }
        );

        foreach ($pre_processors as $pre_processor) {
            $this->pre_processor_manager->with($pre_processor);
        }

        // Build FormOrm Input
        $input = new ilFileInputGUI(
            $this->getField()->getTitle(),
            'field_' . $this->getField()->getId()
        );

        $supported_suffixes = $this->getField()->getSupportedExtensions();
        if (!empty($supported_suffixes)) {
            $input->setSuffixes($supported_suffixes);
        }

        $input->setAllowDeletion(true);
        $this->requiredWorkaroundForInputField($input, $record_id); // TODO CHeck

        return $input;
    }

    private function requiredWorkaroundForInputField(
        ilFileInputGUI $input,
        ?int $record_id
    ): void {
        if ($record_id !== null) {
            $record = ilDclCache::getRecordCache($record_id);
        }

        $this->setupInputField($input, $this->getField());

        //WORKAROUND
        // If field is from type file: if it's required but already has a value it is no longer required as the old value is taken as default without the form knowing about it.
        if ($record_id !== null && $record->getId()) {
            $field_value = $record->getRecordFieldValue($this->getField()->getId());
            if ($field_value) {
                $input->setRequired(false);
            }
        }
        // If this is an ajax request to return the form, input files are currently not supported
        if ($this->ctrl->isAsynch()) {
            $input->setDisabled(true);
        }
    }

    protected function buildFieldCreationInput(
        ilObjDataCollection $dcl,
        string $mode = 'create'
    ): ilRadioOption {
        $opt = parent::buildFieldCreationInput($dcl, $mode);
        foreach ($this->validations->getValidations() as $validation) {
            $input = $validation->getConfigurationInput();
            if ($input === null) {
                continue;
            }

            // PostVars must start with prop_
            $post_var = $input->getPostVar();
            if (!str_starts_with($post_var, 'prop_')) {
                $input->setPostVar('prop_' . $validation->getConfigKey());
            }

            $opt->addSubItem($input);
        }

        return $opt;
    }
}
