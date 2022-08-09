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

namespace ILIAS\CI\MaintenanceInfo\Inventory\Migrator;

use ILIAS\CI\MaintenanceInfo\Inventory\AbstractInventoryItem;
use ILIAS\CI\MaintenanceInfo\Inventory\SerializableInventoryItem;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ConverterCollection
{
    protected array $converters = [];
    protected int $max_version = 0;

    /**
     * @param array $converters
     */
    public function __construct(array $converters)
    {
        foreach ($converters as $converter) {
            if (!$converter instanceof Migrator) {
                throw new \InvalidArgumentException('Migrator must be an instance of Migrator');
            }
            $this->converters[$converter->matchesForVersion()] = $converter;
            $this->max_version = max($this->max_version, $converter->versionAfter());
        }
    }

    public function convertToLatest(int $current_version, array $data) : array
    {
        while ($current_version < $this->max_version) {
            $converter = $this->getConverterForVersion($current_version);
            $data = $converter->convert($data);
            $current_version = $converter->versionAfter();
        };

        return $data;
    }

    private function getConverterForVersion(int $version) : Migrator
    {
        if (!isset($this->converters[$version])) {
            throw new \InvalidArgumentException('No converter found for version ' . $version);
        }
        return $this->converters[$version];
    }


    public function getMaxVersion() : int
    {
        return $this->max_version;
    }
}
