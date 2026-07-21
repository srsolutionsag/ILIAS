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
class DummyFileValidationPreProcessor implements PreProcessor
{
    public function __construct(private Language $lng, private bool $reject)
    {
    }

    public function process(FileStream $stream, Metadata $metadata): ProcessingStatus
    {
        if ($this->reject) {
            return new ProcessingStatus(
                ProcessingStatus::DENIED,
                $this->lng->txt('dummy_denied'),
            );
        }
        return new ProcessingStatus(
            ProcessingStatus::OK,
            self::class . ' noting to do.',
        );
    }
}
