<?php

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

declare(strict_types=1);

use ILIAS\Setup;
use ILIAS\Setup\Config;

/**
 * @author  Tim Schmitz <schmitz@leifos.de>
 */
class ilWebResourceSetupAgent extends Setup\Agent\NullAgent
{
    public function getUpdateObjective(Setup\Config $config = null): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            'WebLinks',
            false,
            new ilDatabaseUpdateStepsExecutedObjective(new ilWebResourceDBUpdateSteps()),
            new ilDatabaseUpdateStepsExecutedObjective(new ilWebResourceDropValidSteps())
        );
    }

    public function getStatusObjective(Setup\Metrics\Storage $storage): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            'Component WebResource',
            true,
            new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilWebResourceDropValidSteps()),
            new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilWebResourceDBUpdateSteps())
        );
    }
}
