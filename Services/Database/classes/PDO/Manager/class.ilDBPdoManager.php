<?php

/**
 * Class ilDBPdoManager
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilDBPdoManager implements ilDBManager, ilDBPdoManagerInterface {

	/**
	 * @var PDO
	 */
	protected $pdo;
	/**
	 * @var ilDBPdo
	 */
	protected $db_instance;


	/**
	 * ilDBPdoManager constructor.
	 *
	 * @param \PDO $pdo
	 * @param \ilDBPdo $db_instance
	 */
	public function __construct(\PDO $pdo, ilDBPdo $db_instance) {
		$this->pdo = $pdo;
		$this->db_instance = $db_instance;
	}


	/**
	 * @param null $database
	 * @return array
	 */
	public function listTables($database = null) {
		$str = 'SHOW TABLES ' . ($database ? ' IN ' . $database : '');
		$r = $this->pdo->query($str);
		$tables = array();
		while ($data = $r->fetchColumn()) {
			if (strpos($data, '_seq') === false) {
				$tables[] = $data;
			}
		}

		return $tables;
	}


	/**
	 * @param null $database
	 * @return array
	 */
	public function listSequences($database = null) {
		$r = $this->pdo->query('SHOW TABLES ' . ($database ? ' IN ' . $database : '') . ' LIKE \'%_seq\'');
		$tables = array();
		while ($data = $r->fetchColumn()) {
			$tables[] = $data;
		}

		return $tables;
	}


	/**
	 * @param $table
	 * @param $name
	 * @param $definition
	 * @return mixed
	 * @throws \ilDatabaseException
	 */
	public function createConstraint($table, $name, $definition) {
		$type = '';
		$name = $this->db_instance->quoteIdentifier($this->getIndexName($name));
		if (!empty($definition['primary'])) {
			$type = 'PRIMARY';
			$name = 'KEY';
		} elseif (!empty($definition['unique'])) {
			$type = 'UNIQUE';
		}
		if (empty($type)) {
			throw new ilDatabaseException('invalid definition, could not create constraint');
		}

		$table = $this->db_instance->quoteIdentifier($table);
		$query = "ALTER TABLE $table ADD $type $name";
		$fields = array();
		foreach (array_keys($definition['fields']) as $field) {
			$fields[] = $this->db_instance->quoteIdentifier($field);
		}
		$query .= ' (' . implode(', ', $fields) . ')';

		return $this->pdo->exec($query);
	}


	/**
	 * @param $seq_name
	 * @param int $start
	 * @param array $options
	 */
	public function createSequence($seq_name, $start = 1, $options = array()) {
		$sequence_name = $this->db_instance->quoteIdentifier($this->getSequenceName($seq_name));
		$seqcol_name = $this->db_instance->quoteIdentifier(ilDBConstants::SEQUENCE_COLUMNS_NAME);

		$options_strings = array();

		if (!empty($options['comment'])) {
			$options_strings['comment'] = 'COMMENT = ' . $this->db_instance->quote($options['comment'], 'text');
		}

		if (!empty($options['charset'])) {
			$options_strings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];
			if (!empty($options['collate'])) {
				$options_strings['charset'] .= ' COLLATE ' . $options['collate'];
			}
		}

		$type = false;
		if (!empty($options['type'])) {
			$type = $options['type'];
		}
		if ($type) {
			$options_strings[] = "ENGINE = $type";
		}

		$query = "CREATE TABLE $sequence_name ($seqcol_name INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ($seqcol_name))";

		if (!empty($options_strings)) {
			$query .= ' ' . implode(' ', $options_strings);
		}
		$this->pdo->exec($query);

		if ($start == 1) {
			return true;
		}

		$query = "INSERT INTO $sequence_name ($seqcol_name) VALUES (" . ($start - 1) . ')';
		$this->pdo->exec($query);

		return true;
	}



	//
	// ilDBPdoManagerInterface
	//
	/**
	 * @param $idx
	 * @return string
	 */
	public function getIndexName($idx) {
		return sprintf(ilDBConstants::INDEX_FORMAT, preg_replace('/[^a-z0-9_\$]/i', '_', $idx));
	}


	/**
	 * @param $sqn
	 * @return string
	 */
	public function getSequenceName($sqn) {
		return sprintf(ilDBConstants::SEQUENCE_FORMAT, preg_replace('/[^a-z0-9_\$.]/i', '_', $sqn));
	}
}
