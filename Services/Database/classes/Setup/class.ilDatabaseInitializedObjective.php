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

class ilDatabaseInitializedObjective implements Setup\Objective
{
    public function getHash(): string
    {
        return hash("sha256", self::class);
    }

    public function getLabel(): string
    {
        return "The database object is initialized.";
    }

    public function isNotable(): bool
    {
        return true;
    }

    /**
     * @return array<\ilIniFilesLoadedObjective>|array<\ilDatabaseConfigStoredObjective|\ilDatabasePopulatedObjective>
     */
    public function getPreconditions(Setup\Environment $environment): array
    {
        // If there is no config for the database the existing config seems
        // to be ok, and we can just connect.
        if (!$environment->hasConfigFor("database")) {
            return [
                new ilIniFilesLoadedObjective()
            ];
        }

        $config = $environment->getConfigFor("database");
        return [
            new ilDatabasePopulatedObjective($config),
            new ilDatabaseConfigStoredObjective($config)
        ];
    }

    public function achieve(Setup\Environment $environment): Setup\Environment
    {
        if ($environment->getResource(Setup\Environment::RESOURCE_DATABASE)) {
            return $environment;
        }
        /** @var ilIniFile $client_ini */
        $client_ini = $environment->getResource(Setup\Environment::RESOURCE_CLIENT_INI);

        $type = $client_ini->readVariable("db", "type");
        if ($type === "" || !in_array($type, [ilDBConstants::TYPE_INNODB, ilDBConstants::TYPE_PDO_MYSQL_INNODB])) {
            /** @var Setup\CLI\IOWrapper $io */
            $io = $environment->getResource(Setup\Environment::RESOURCE_ADMIN_INTERACTION);
            $io->inform(
                "Saved database type is not supported anymore. Switching to InnoDB and store this to client.ini."
            );
            $type = ilDBConstants::TYPE_INNODB;
            $client_ini->setVariable("db", "type", $type);
            $client_ini->write();
        }

        $db = \ilDBWrapperFactory::getWrapper($type);
        $db->initFromIniFile($client_ini);
        $connect = $db->connect(true);
        if (!$connect) {
            throw new Setup\UnachievableException(
                "Database cannot be connected."
            );
        }

        return $environment->withResource(Setup\Environment::RESOURCE_DATABASE, $db);
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment): bool
    {
        return $environment->getResource(Setup\Environment::RESOURCE_CLIENT_INI) !== null;
    }
}
