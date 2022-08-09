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

namespace ILIAS\CI\MaintenanceInfo\Inventory\Person;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItem;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Role;
use ILIAS\CI\MaintenanceInfo\Inventory\SerializableInventoryItem;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Person extends AbstractInventoryItem implements SerializableInventoryItem
{
    protected ?int $docu_user_id = null;
    protected ?string $docu_user_name = null;
    protected ?string $github_user_name = null;
    protected ?string $mantis_user_name = null;
    protected ?string $email = null;

    public function getDocuUserID(): ?int
    {
        return $this->docu_user_id;
    }

    public function getDocuUserName(): ?string
    {
        return $this->docu_user_name;
    }


    public function getGithubUserName(): ?string
    {
        return $this->github_user_name;
    }

    public function getMantisUserName(): ?string
    {
        return $this->mantis_user_name;
    }


    public function setDocuUserID(int $docu_user_id): void
    {
        $this->docu_user_id = $docu_user_id;
    }

    public function setDocuUserName(string $docu_user_name): void
    {
        $this->docu_user_name = $docu_user_name;
    }

    public function setGithubUserName(string $github_user_name): void
    {
        $this->github_user_name = $github_user_name;
    }

    public function setMantisUserName(string $mantis_user_name): void
    {
        $this->mantis_user_name = $mantis_user_name;
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }


    public function setEmail(string $email): void
    {
        $this->email = $email;
    }


}
