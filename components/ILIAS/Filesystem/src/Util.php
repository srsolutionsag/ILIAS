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

namespace ILIAS\Filesystem;

use ILIAS\Refinery\FileName\FileName;

/**
 * This Util class is a collection of static helper methods to provide file system related functionality.
 * Currently you can use it to sanitize file names which are compatible with the ILIAS file system.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Util
{
    /**
     * @deprecated Use the \ILIAS\Refinery\FileName\FileName transformation instead
     */
    public static function sanitizeFileName(string $filename): string
    {
        return (new FileName())->transform($filename);
    }
}
