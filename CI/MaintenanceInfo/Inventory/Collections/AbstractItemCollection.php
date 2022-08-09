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

namespace ILIAS\CI\MaintenanceInfo\Inventory\Collections;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItem;
use ILIAS\CI\MaintenanceInfo\Inventory\Component;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Person;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class AbstractItemCollection implements Collection
{
    private array $items = [];

    abstract protected function resolveIndex(AbstractInventoryItem $item): ?string;

    public function add(AbstractInventoryItem $item): AbstractInventoryItem
    {
        $i = $this->resolveIndex($item);
        if ($i === null) {
            return $item;
        }
        if (!isset($this->items[$i])) {
            return $this->items[$i] = $item;
        } else {
            return $this->merge($this->items[$i], $item);
        }
    }

    public function populate(AbstractInventoryItem $item): AbstractInventoryItem
    {
        return $this->add($item);
    }

    protected function merge(AbstractInventoryItem $item_one, AbstractInventoryItem $item_two): AbstractInventoryItem
    {
        $data_one = $item_one->jsonSerialize();
        $data_two = $item_two->jsonSerialize();

        $merged = [];

        foreach ($data_one as $key => $value) {
            if ($value instanceof AbstractInventoryItem) {
                $merged[$key] = $this->merge($value, $data_two[$key]);
                continue;
            }
            if (is_array($value)) {
                $merged[$key] = array_merge($value, $data_two[$key]);
                continue;
            }
            $merged[$key] = $value;
        }

        return $item_one->jsonDeserialize($merged);
    }

    public function getAll(): array
    {
        return $this->items;
    }

}
