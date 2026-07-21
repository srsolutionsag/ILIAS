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

/**
 * @noinspection AutoloadingIssuesInspection
 */

use ILIAS\DataCollection\Validation\File\FileValidationCollection;
use ILIAS\DataCollection\Validation\File\SuffixFileValidation;

class ilDclFileFieldModel extends ilDclBaseFieldModel
{
    private FileValidationCollection $validations;
    protected ilFileServicesSettings $file_settings;

    public function __construct(int $a_id = 0)
    {
        global $DIC;
        $this->validations = $DIC[FileValidationCollection::class];
        $this->file_settings = $DIC->fileServiceSettings();
        parent::__construct($a_id);
    }

    public function allowFilterInListView(): bool
    {
        return false;
    }

    public function getValidFieldProperties(): array
    {
        return iterator_to_array($this->validations->getAllPostVars());
    }

    public function getSupportedExtensions(): array
    {
        $file_types = [];

        foreach ($this->getExtensions() as $i => $type) {
            if (
                in_array($type, $this->file_settings->getWhiteListedSuffixes()) &&
                !in_array($type, $this->file_settings->getBlackListedSuffixes())
            ) {
                $file_types[] = $type;
            }
        }

        return $file_types;
    }

    protected function getExtensions(): array
    {
        $validation = $this->validations->get(SuffixFileValidation::class);
        if ($validation === null) {
            return [];
        }

        $types = $this->getProperty($validation->getConfigKey());
        if ($types === null) {
            return [];
        }

        return explode(',', str_replace(' ', '', $types));
    }
}
