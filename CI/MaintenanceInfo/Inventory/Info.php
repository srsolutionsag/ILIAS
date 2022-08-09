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

namespace ILIAS\CI\MaintenanceInfo\Inventory;

use ILIAS\CI\MaintenanceInfo\Inventory\Person\Coordinators;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Maintainers;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\TestcaseWriters;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\TestercaseWriter;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Testers;
use SebastianBergmann\CodeCoverage\Report\Xml\Tests;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Info extends AbstractInventoryItem
{
    const INFO_VERSION = 2;

    protected ?Component $component = null;
    protected ?Path $path = null;
    protected int $info_version = self::INFO_VERSION;
    protected ?bool $check = null;

    public function setComponent(Component $component): void
    {
        $this->component = $component;
    }


    public function getComponent(): Component
    {
        return $this->component ?? new Component();
    }


    public function getPath(): Path
    {
        return $this->path ?? new Path();
    }


    public function getInfoVersion(): int
    {
        return $this->info_version;
    }


}
