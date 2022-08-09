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

namespace ILIAS\CI\MaintenanceInfo\Inventory\Role;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItem;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Flavours\Flavour;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;
use ILIAS\CI\MaintenanceInfo\Inventory\SerializableInventoryItem;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Role extends AbstractInventoryItem implements SerializableInventoryItem
{
    const ROLE_COORDINATOR = 'Coordinator';
    const ROLE_FIRST_MAINTAINER = 'First Maintainer';
    const ROLE_SECOND_MAINTAINER = 'Second Maintainer';
    const ROLE_IMPLICIT_MAINTAINER = 'Implicit Maintainer';
    const ROLE_TESTER = 'Tester';
    const ROLE_TESTCASE_WRITER = 'Testcase Writer';
    const ROLE_UNKNOWN = 'Unknown';


    protected ?string $name = null;
    protected ?Person $person = null;


    public function getName(): ?string
    {
        return $this->name;
    }


    public function setName(?string $name): void
    {
        $this->name = $name;
    }


    public function getPerson(): ?Person
    {
        return $this->person;
    }


    public function setPerson(?Person $person): void
    {
        $this->person = $person;
    }


}
