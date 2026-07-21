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
use ILIAS\Language\Language;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class FileValidationCollection
{

    /**
     * @var FileValidation[]
     */
    private array $validations;

    public function __construct(
        private readonly Language $lng,
        FileValidation ...$validation
    ) {
        foreach ($validation as $v) {
            $this->validations[$v::class] = $v;
        }
    }

    public function get(string $specific_fqdn): ?FileValidation
    {
        return $this->validations[$specific_fqdn] ?? null;
    }

    public function getValidations(): array
    {
        return $this->validations;
    }

    public function getAllConfigurationInputs(): \Generator
    {
        foreach ($this->validations as $validation) {
            $input = $validation->getConfigurationInput();
            if ($input === null) {
                continue;
            }
            yield $input;
        }
    }
    public function getPreProcessor(FileValidation $validation, mixed $configuration): PreProcessor
    {
        return $validation->getPreprocessor($configuration, $this->lng);
    }

    /**
     * @param \Closure(FileValidation): mixed $configuration_resolver returns the stored configuration of a validation
     * @return \Generator<PreProcessor>
     */
    public function getAllPreProcessors(\Closure $configuration_resolver): \Generator
    {
        foreach ($this->validations as $validation) {
            yield $this->getPreProcessor($validation, $configuration_resolver($validation));
        }
    }

    public function getAllPostVars(): \Generator
    {
        foreach ($this->validations as $validation) {
            $config_key = $validation->getConfigKey();
            if ($config_key === null) {
                continue;
            }
            yield $config_key;
        }
    }
}
