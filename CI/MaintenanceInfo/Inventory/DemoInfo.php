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

use ILIAS\CI\MaintenanceInfo\Inventory\Person\Coordinator;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Coordinators;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Maintainer;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Maintainers;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Tester;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Testers;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class DemoInfo extends Info
{
    public function __construct()
    {
        $this->coordinators = new Coordinators();
        $this->coordinators->add(
            (new Coordinator())
                ->jsonDeserialize(
                    ['ilias_user_id' => 41, 'github_user_name' => 'chfsx', 'mantis_user_name' => 'fschmid']
                )
        );
        $this->coordinators->add(
            (new Coordinator())
                ->jsonDeserialize(
                    ['ilias_user_id' => 89, 'github_user_name' => 'lorem', 'mantis_user_name' => 'ipsum']
                )
        );
        $this->maintainers = new Maintainers();
        $this->maintainers->add(
            (new Maintainer())
                ->jsonDeserialize(
                    ['ilias_user_id' => 42, 'github_user_name' => 'chfsx', 'mantis_user_name' => 'fschmid']
                )
        );

        $this->testers = new Testers();
        $this->testers->add(
            (new Tester())
                ->jsonDeserialize(
                    ['ilias_user_id' => 43, 'github_user_name' => 'chfsx', 'mantis_user_name' => 'fschmid']
                )
        );
        $this->component = new Component();
        $this->component->setTitle('Demo Component');

        $this->path = (new Path())->jsonDeserialize(['directory' => 'CI/MaintenanceInfo']);
    }
}
