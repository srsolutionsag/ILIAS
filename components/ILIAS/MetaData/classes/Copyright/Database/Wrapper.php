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

namespace ILIAS\MetaData\Copyright\Database;

class Wrapper implements WrapperInterface
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function nextID(string $table): int
    {
        return $this->db->nextId($table);
    }

    public function query(string $query): \Generator
    {
        $result = $this->db->query($query);

        while ($row = $this->db->fetchAssoc($result)) {
            yield $row;
        }
    }

    public function manipulate(string $query): void
    {
        $this->db->manipulate($query);
    }

    public function update(string $table, array $values, array $where): void
    {
        $this->db->update(
            $table,
            $values,
            $where
        );
    }

    public function insert(string $table, array $values): void
    {
        $this->db->insert(
            $table,
            $values
        );
    }

    public function quoteInteger(int $integer): string
    {
        return $this->db->quote($integer, \ilDBConstants::T_INTEGER);
    }
}
