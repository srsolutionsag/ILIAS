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

use ILIAS\CI\MaintenanceInfo\Inventory\Collections\Collection;
use ILIAS\CI\MaintenanceInfo\Inventory\Collections\Collections;
use ILIAS\CI\MaintenanceInfo\Inventory\Collections\ComponentCollection;
use ILIAS\CI\MaintenanceInfo\Inventory\Collections\PersonCollection;
use ILIAS\CI\MaintenanceInfo\Inventory\Migrator\ConverterCollection;
use ILIAS\CI\MaintenanceInfo\Inventory\Migrator\FromNoVersionMigrator;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Role;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Roles;
use ILIAS\CI\MaintenanceInfo\Storage\InfoFile;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Repository
{
    const INFO_VERSION = 'info_version';
    const MAINTENANCE_JSON = 'maintenance.json';
    private array $mapping = [
        'persons' => Person\Persons::class,
        'person' => Person\Person::class,
        'role' => Role::class,
        'roles' => Roles::class,
        'component' => Component::class,
        'paths' => Paths::class,
        'path' => Path::class,
        'info' => Info::class,
    ];
    protected ConverterCollection $converters;
    protected Collections $collections;

    public function __construct(Collections $collections)
    {
        $this->converters = new ConverterCollection([
            new FromNoVersionMigrator()
        ]);
        $this->collections = $collections;
    }


    public function fromInfoFile(InfoFile $info_file): Info
    {
        $content = $info_file->getContent();
        $current_version = $content[self::INFO_VERSION] ?? 1; // There was no version before
        $content = $this->converters->convertToLatest($current_version, $content);


        // Ensure current Directory is set
        $content = array_merge($content, [
            'path' => [
                'directory' => ltrim(dirname($info_file->getPath()), "./"),
            ],
        ]);

        $instances = $this->translate()($content);

        // Set Version to current
        $info = new Info();
        $data = array_merge(
            [self::INFO_VERSION => $this->converters->getMaxVersion()],
            $instances
        );
        $info->jsonDeserialize($data);
        // update Paths
        $component = $info->getComponent();
        $component->addPath($info->getPath());

        return $info;
    }

    public function toInfoFile(Info $info, ?string $path = null): InfoFile
    {
        return new InfoFile(
            $path ?? $info->getPath()->getDirectory() . '/' . self::MAINTENANCE_JSON,
            $info->jsonSerialize()
        );
    }


    protected function translate(): \Closure
    {
        return function ($data, ?string $type = null): array {
            if (!is_array($data)) {
                return [];
            }
            $instances = [];
            foreach ($data as $key => $value) {
                if ($type === null && (!isset($this->mapping[$key]) || !class_exists($this->mapping[$key]))) {
                    $instances[$key] = $value;
                } else {
                    $class = $type ?? $this->mapping[$key];
                    if (!is_a($class, SerializableInventoryItem::class, true)) {
                        continue;
                    }
                    $instance = new $class();
                    if ($instance instanceof AbstractInventoryItemCollection) {
                        $value = $this->translate()($value, $instance->holds());
                    } elseif ($instance instanceof SerializableInventoryItem) {
                        $value = $this->translate()($value);
                    }
                    $instance->jsonDeserialize($value);
                    if ($instance instanceof Person\Person) {
                        $instance = $this->collections->persons()->populate($instance);
                    }
                    if ($instance instanceof Component) {
                        $instance = $this->collections->components()->populate($instance);
                    }
                    $instances[$key] = $instance;
                }
            }
            return $instances;
        };
    }
}
