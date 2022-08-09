<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Infrastructure;

use ILIAS\CI\MaintenanceInfo\Inventory\Info;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Role;

class PrimaryPersonResolver
{
    public function resolve(Info $info): \ILIAS\CI\MaintenanceInfo\Inventory\Person\Person
    {
        $component = $info->getComponent();

        foreach ($component->getRoles()->get() as $role) {
            if ($role->getName() === Role::ROLE_COORDINATOR) {
                return $role->getPerson();
            }
            if ($role->getName() === Role::ROLE_FIRST_MAINTAINER) {
                return $role->getPerson();
            }
        }

        return (new Person())->jsonDeserialize([
            'docu_user_name' => null,
            'docu_user_id' => 0,
            'role' => (new Role())->jsonDeserialize([
                'name' => Role::ROLE_UNKNOWN
            ]),
        ]);
    }
}
