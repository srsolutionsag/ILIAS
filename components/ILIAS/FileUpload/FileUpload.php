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

namespace ILIAS;

use ILIAS\Component\Component;
use ILIAS\FileUpload\Activities\FileParameter;
use ILIAS\FileUpload\Activities\TempFileStore;

class FileUpload implements Component
{
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        $internal[TempFileStore::class] = static fn(): TempFileStore => new TempFileStore();

        /**
         * How an activity takes a file: the description of the parameters and
         * the reading of them. Uploading is no activity of its own - a user does
         * not want a handle, they want a file somewhere - so the bytes travel
         * with the activity that uses them, and this is the code doing that,
         * shared by all of them. See src/Activities/README.md.
         */
        $internal[FileParameter::class] = static fn(): FileParameter => new FileParameter(
            $internal[TempFileStore::class],
        );

        $provide[FileParameter::class] = static fn(): FileParameter => $internal[FileParameter::class];
        $provide[TempFileStore::class] = static fn(): TempFileStore => $internal[TempFileStore::class];
    }
}
