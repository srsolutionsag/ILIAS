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

namespace ILIAS\CI\MaintenanceInfo\Inventory\Collections;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItem;
use ILIAS\CI\MaintenanceInfo\Inventory\Component;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Collections
{
    protected PersonCollection $person_collection;
    protected ComponentCollection $components_collection;

    public function __construct()
    {
        $this->person_collection = new PersonCollection();
        $this->components_collection = new ComponentCollection();
    }

    public function persons(): PersonCollection
    {
        return $this->person_collection;
    }

    public function components(): ComponentCollection
    {
        return $this->components_collection;
    }

}
