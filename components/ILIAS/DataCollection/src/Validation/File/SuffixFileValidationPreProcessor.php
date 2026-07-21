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
use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\Language\Language;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class SuffixFileValidationPreProcessor implements PreProcessor
{
    /**
     * @var string[] lowercase suffixes without leading dot
     */
    public function __construct(
        private readonly Language $lng,
        private array $allowed_suffixes
    ) {

    }

    public function process(FileStream $stream, Metadata $metadata): ProcessingStatus
    {
        // no configured suffixes means no restriction at all
        if ($this->allowed_suffixes === []) {
            return new ProcessingStatus(
                ProcessingStatus::OK,
                'No supported suffixes configured, nothing to check.'
            );
        }

        $this->allowed_suffixes = array_map('strtolower', $this->allowed_suffixes);
        $suffix = strtolower(pathinfo($metadata->getFilename(), PATHINFO_EXTENSION));

        if ($suffix === '') {
            return new ProcessingStatus(
                ProcessingStatus::DENIED,
                sprintf(
                    $this->lng->txt('dcl_file_suffix_missing'),
                    implode(', ', $this->allowed_suffixes)
                )
            );
        }

        if (!in_array($suffix, $this->allowed_suffixes, true)) {
            return new ProcessingStatus(
                ProcessingStatus::DENIED,
                sprintf(
                    $this->lng->txt('dcl_file_suffix_not_supported'),
                    $suffix,
                    implode(', ', $this->allowed_suffixes)
                )
            );
        }

        return new ProcessingStatus(
            ProcessingStatus::OK,
            'The suffix ' . $suffix . ' is supported.'
        );
    }
}
