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

use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Persons;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Roles;


/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Component extends AbstractInventoryItem
{
    protected ?string $title = null;
    protected ?bool $testing_needed = true;
    protected ?Paths $paths = null;
    protected ?Roles $roles = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }


    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTestingNeeded(): ?bool
    {
        return $this->testing_needed;
    }

    public function setTestingNeeded(bool $testing_needed): void
    {
        $this->testing_needed = $testing_needed;
    }


    public function getRoles(): Roles
    {
        return $this->roles ?? $this->roles = new Roles();
    }


    public function setRoles(Roles $roles): void
    {
        $this->roles = $roles;
    }


    public function getPaths(): Paths
    {
        return $this->paths ?? $this->paths = new Paths();
    }

    public function addPath(Path $path): void
    {
        foreach ($this->getPaths()->get() as $existing_path) {
            if ($path->getDirectory() === $existing_path->getDirectory()) {
                return;
            }
        }
        $this->getPaths()->add($path);
    }

}
