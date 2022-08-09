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

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class AbstractInventoryItemCollection implements SerializableInventoryItem
{
    /**
     * @var SerializableInventoryItem[]
     */
    protected array $collection = [];

    public function jsonSerialize(): mixed
    {
        return $this->collection;
    }

    abstract public function holds(): string;

    public function jsonDeserialize(array $data): self
    {
        $this->collection = [];
        foreach ($data as $item) {
            $this->collection[] = $item;
        }
        return $this;
    }


    public function add(SerializableInventoryItem $item): void
    {
        $this->collection[] = $item;
    }

    public function get(): array
    {
        return $this->collection;
    }

    public function set(array $collection): void
    {
        $this->collection = $collection;
    }

}
