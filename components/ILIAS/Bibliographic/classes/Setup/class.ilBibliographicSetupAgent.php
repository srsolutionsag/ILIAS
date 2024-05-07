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

use ILIAS\Refinery;
use ILIAS\Setup;

/**
 * Class ilBibliographicSetupAgent
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
final class ilBibliographicSetupAgent implements Setup\Agent
{
    use Setup\Agent\HasNoNamedObjective;

    /**
     * @var string component dir within ilias's data dir
     */
    public const COMPONENT_DIR = 'bibl';

    /**
     * @inheritdoc
     */
    public function hasConfig(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getArrayToConfigTransformation(): Refinery\Transformation
    {
        throw new LogicException("ilBibliographicSetupAgent has no config.");
    }

    /**
     * @inheritdoc
     */
    public function getInstallObjective(Setup\Config $config = null): Setup\Objective
    {
        return new ilFileSystemComponentDataDirectoryCreatedObjective(
            self::COMPONENT_DIR,
            ilFileSystemComponentDataDirectoryCreatedObjective::DATADIR
        );
    }

    /**
     * @inheritdoc
     */
    public function getUpdateObjective(Setup\Config $config = null): Setup\Objective
    {
        return new Setup\ObjectiveCollection('Setup Bibliografic directories and database', true, ...[
            new ilDatabaseUpdateStepsExecutedObjective(
                new ilBibliograficDB80()
            )
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getBuildObjective(): Setup\Objective
    {
        return new Setup\Objective\NullObjective();
    }

    /**
     * @inheritdoc
     */
    public function getStatusObjective(Setup\Metrics\Storage $storage): Setup\Objective
    {
        return new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new ilBibliograficDB80());
    }

    /**
     * @inheritDoc
     */
    public function getMigrations(): array
    {
        return [];
    }
}
