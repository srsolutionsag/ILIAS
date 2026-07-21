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
 */

declare(strict_types=1);

namespace ILIAS\DataCollection\Validation\File;

use ILIAS\FileUpload\Processor\PreProcessor;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\Language\Language;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class DummyFileValidation implements FileValidation
{

    public function __construct(
        private Language $lng
    ) {
    }

    public function getConfigKey(): ?string
    {
        return 'dummy_file';
    }

    public function getConfigurationInput(): ?\ilSubEnabledFormPropertyGUI
    {
        $prop_filetype = new \ilCheckboxInputGUI(
            $this->lng->txt('dummy'),
        );

        $prop_filetype->setInfo($this->lng->txt('dummy_info'));

        return $prop_filetype;
    }

    public function getPreprocessor(mixed $configuration, Language $lng): PreProcessor
    {
        return new DummyFileValidationPreProcessor($lng, (bool) $configuration);
    }
}
