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

namespace ILIAS\CI\MaintenanceInfo\Inventory\Migrator;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItem;
use ILIAS\CI\MaintenanceInfo\Inventory\SerializableInventoryItem;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface Migrator
{
    public function matchesForVersion() : int;

    public function versionAfter() : int;

    public function convert(array $data) : array;
}
