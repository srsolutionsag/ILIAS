<?php

/* Copyright (c) 2019 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

use ILIAS\Setup;

class ilDatabaseCollationSetObjective implements Setup\Objective
{
    protected $db_name = null;
    protected $collation = null;
    /**
     * @var ilDBInterface
     */
    protected $db;
    /**
     * @var ilIniFile
     */
    protected $client_ini;
    /**
     * @var Setup\CLI\IOWrapper
     */
    protected $io;

    public function getHash() : string
    {
        return hash("sha256", self::class);
    }

    public function getLabel() : string
    {
        return "All collations are set to the default.";
    }

    public function isNotable() : bool
    {
        return true;
    }

    public function getPreconditions(Setup\Environment $environment) : array
    {
        return [
            new \ilDatabaseUpdatedObjective()
        ];
    }

    protected function initPreconditions(Setup\Environment $environment) : void
    {
        $this->db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $this->client_ini = $environment->getResource(Setup\Environment::RESOURCE_CLIENT_INI);
        $this->io = $environment->getResource(Setup\Environment::RESOURCE_ADMIN_INTERACTION);

        if ($this->db_name === null) {
            $this->db_name = $this->client_ini->readVariable('db', 'name');
        }

        if ($this->collation === null) {

            $r = $this->db->fetchObject($this->db->query("SELECT @@character_set_database AS char_set, @@collation_database AS collation;"));
            if (!isset($r->collation) || $r->collation === null) {
                throw new Setup\UnachievableException('no default or selected collation found, abort.');
            }
            $this->collation = $r->collation;
        }
    }

    public function achieve(Setup\Environment $environment) : Setup\Environment
    {
        $this->initPreconditions($environment);

        if ($this->io->confirmExplicit('do you want to set the collation of all tables to ' . $this->collation,
            'yes')) {
            foreach ($this->db->listTables() as $table_name) {
                try {
                    $this->db->manipulate("ALTER TABLE " . $this->db->quoteIdentifier($table_name) . " COLLATE $this->collation;");
                } catch (Throwable $t) {
                    $this->io->inform('failed to convert table ' . $table_name);
                }
            }
        }

        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment) : bool
    {
        $this->initPreconditions($environment);

        $q = "SELECT DISTINCT TABLE_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = '$this->db_name' 
                        AND COLLATION_NAME IS NOT NULL
                        AND COLLATION_NAME != '$this->collation';";
        $rows = (int) $this->db->numRows($this->db->query($q));

        return $rows > 1;
    }
}
