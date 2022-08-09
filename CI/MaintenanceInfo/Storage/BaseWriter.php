<?php

declare(strict_types=1);

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

namespace ILIAS\CI\MaintenanceInfo\Storage;

use ILIAS\CI\MaintenanceInfo\Inventory\Info;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class BaseWriter
{
    protected Converter $converter;

    public function __construct()
    {
        $this->converter = new Converter();
    }

    public function writeInfoFile(InfoFile $info_file): void
    {
        file_put_contents($info_file->getPath(), $this->converter->infoFileToString($info_file));
    }

    public function writeMarkDownFile(InfoFile $info_file, Info $info): void
    {
        file_put_contents(
            dirname($info_file->getPath()) . '/INFO.md',
            $this->converter->infoFileToMarkDown($info_file, $info)
        );
    }

    public function updateMaintenanceMarkdown(array $infos): void
    {
        $file = './docs/development/maintenance.md';
        $begin = "[//]: # (BEGIN %s)";
        $end = "[//]: # (END %s)";
        $seperator = "<!-- REMOVE -->";
        $md = strstr(file_get_contents($file), $seperator, true) . $seperator . "\n";

    }
}
