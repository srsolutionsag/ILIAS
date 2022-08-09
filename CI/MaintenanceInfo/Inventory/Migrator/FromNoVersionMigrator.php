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

use ILIAS\CI\MaintenanceInfo\Inventory\Migrator\Helpers\DocuUsernameFactory;
use ILIAS\CI\MaintenanceInfo\Inventory\Info;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Flavours\Flavour;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Maintainer;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Role;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class FromNoVersionMigrator implements Migrator
{
    protected DocuUsernameFactory $docu_username;

    public function __construct()
    {
        $this->docu_username = new DocuUsernameFactory();
    }

    public function matchesForVersion(): int
    {
        return 1;
    }

    public function versionAfter(): int
    {
        return 2;
    }


    public function convert(array $data): array
    {
        $roles = [];

        $to_roles = function($data, string $role) use (&$roles) {
            if(!is_array($data)) {
                $data = [$data];
            }
            foreach ($data as $item) {
                if (strlen($item) === 0) {
                    continue;
                }
                $roles[] = [
                    'name' => $role,
                    'person' => $this->docu_username->fromString($item)->__asArray()
                ];
            }
        };

        $to_roles($data['coordinator'] ?? [], Role::ROLE_COORDINATOR);
        $to_roles($data['first_maintainer'] ?? [], Role::ROLE_FIRST_MAINTAINER);
        $to_roles($data['second_maintainer'] ?? [], Role::ROLE_SECOND_MAINTAINER);
        $to_roles($data['implicit_maintainers'] ?? [], Role::ROLE_IMPLICIT_MAINTAINER);
        $to_roles($data['tester'] ?? [], Role::ROLE_TESTER);
        $to_roles($data['testcase_writer'] ?? [], Role::ROLE_TESTCASE_WRITER);

        $component = $data['belong_to_component'] ?? null;
        $component = $component === 'None' ? null : $component;

        return [
            'version' => Info::INFO_VERSION,
            'component' => [
                'title' => $component,
                'roles' => $roles,
//                'paths' => $paths,
            ],
        ];
    }
}
