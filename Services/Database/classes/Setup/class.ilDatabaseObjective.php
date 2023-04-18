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

abstract class ilDatabaseObjective implements Setup\Objective
{
    protected \ilDatabaseSetupConfig $config;

    public function __construct(\ilDatabaseSetupConfig $config)
    {
        $this->config = $config;
    }

    protected function getDBInstanceForType(Setup\Environment $env): ilDBInterface
    {
        /** @var Setup\CLI\IOWrapper $io */
        $io = $env->getResource(Setup\Environment::RESOURCE_ADMIN_INTERACTION);
        $type = $this->config->getType();
        if (!in_array($type, [ilDBConstants::TYPE_INNODB, ilDBConstants::TYPE_PDO_MYSQL_INNODB])) {
            if (!$io->confirmOrDeny(
                "The database type '$type' is not supported by ILIAS anymore. " .
                "Please use 'innodb' instead. " .
                "Do you want to continue with 'innodb'?"
            )) {
                throw new Setup\UnachievableException(
                    "Database type '$type' is not supported by ILIAS anymore. " .
                    "Please use 'innodb' instead in your config."
                );
            };
            $type = ilDBConstants::TYPE_INNODB;
        }
        return \ilDBWrapperFactory::getWrapper($type);
    }
}
