<?php

namespace ILIAS\MainMenu\Administration;

use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\hasTitle;
use ILIAS\UI\Component\Table as I;
use ilMMItemRepository;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;
use ilObjMainMenuAccess;

/**
 *
 */
class DataRetrievalSubItems implements I\DataRetrieval
{
    use Hasher;
    private \ilLanguage $lng;
    private ilMMItemRepository $item_repository;
    private ilObjMainMenuAccess $access;

    public const IDENTIFIER = 'identifier';
    public const F_TABLE_ENTRY_STATUS = 'entry_status';
    public const F_TABLE_ALL_VALUE = 1;
    public const F_TABLE_ONLY_ACTIVE_VALUE = 2;
    public const F_TABLE_ONLY_INACTIVE_VALUE = 3;

    public function __construct(
        ilMMItemRepository $item_repository,
        ilObjMainMenuAccess $access
    ) {
        global $DIC;
        $this->lng = $DIC['lng'];
        $this->access = $access;
        $this->item_repository = $item_repository;
    }

    public function getRows(
        I\DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        global $DIC;
        static $parent_identification_string;
        $records = $this->getRecords($order);
        foreach ($records as $idx => $record) {
            $item_ident = $DIC->globalScreen()->identification()->fromSerializedIdentification($record['identification']);
            $item_facade = $this->item_repository->repository()->getItemFacade($item_ident);
            if ($item_facade->isChild()) {
                if (!$parent_identification_string ||
                    $parent_identification_string !== $item_facade->getParentIdentificationString()) {
                    $parent_identification_string = $item_facade->getParentIdentificationString();
                    $current_parent_identification = $this->item_repository->resolveIdentificationFromString(
                        $parent_identification_string
                    );
                    $current_parent_item = $this->item_repository->getSingleItem($current_parent_identification);
                    $record['parent_title'] = $current_parent_item instanceof hasTitle ? $current_parent_item->getTitle() : "-";
                    $record['native_parent_id'] = $current_parent_item->getProviderIdentification()->serialize();
                    $record['parent_id'] = $this->hash($current_parent_item->getProviderIdentification()->serialize());
                }
            }
            $record['identifier'] = $record['identification'];
            $record['id'] = $this->hash($item_facade->getId());
            $record['native_id'] = $item_facade->getId();
            $record['title'] = $item_facade->getDefaultTitle();
            $record['parent'] = $current_parent_item instanceof hasTitle ? $current_parent_item->getTitle() : "-";
            $record['type'] = $current_parent_item->getTitle();
            $record['status'] = $item_facade->getStatus();
            $record['provider'] = $item_facade->getProviderNameForPresentation();
            $row_id = (string) $record['id'];

            yield $row_builder->buildDataRow($row_id, $record)
                ->withDisabledAction("edit", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("translate", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("delete", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("move", !$this->access->hasUserPermissionTo('write'));
        }
    }

    private function resolveData(): array
    {
        global $DIC;
        $sub_items_for_table = $this->item_repository->getSubItemsForTable();

        // populate with facade
        array_walk($sub_items_for_table, function (&$item) use ($DIC) {
            $item_ident = $DIC->globalScreen()->identification()->fromSerializedIdentification($item['identification']);
            $item_facade = $this->item_repository->repository()->getItemFacade($item_ident);
            $item['facade'] = $item_facade;
        });

        // filter active/inactive
        array_filter($sub_items_for_table, function ($item_facade) {
            if (!isset($this->filter[self::F_TABLE_ENTRY_STATUS])) {
                return true;
            }
            if ($this->filter[self::F_TABLE_ENTRY_STATUS] !== self::F_TABLE_ALL_VALUE) {
                return true;
            }
            if ($this->filter[self::F_TABLE_ENTRY_STATUS] == self::F_TABLE_ONLY_ACTIVE_VALUE && !$item_facade->isActivated()) {
                return false;
            }
            if ($this->filter[self::F_TABLE_ENTRY_STATUS] == self::F_TABLE_ONLY_INACTIVE_VALUE && $item_facade->isActivated()) {
                return false;
            }
            return true;
        });

        return $sub_items_for_table;
    }

    protected function getRecords(Order $order): array
    {
        $records = $this->resolveData();
        [$order_field, $order_direction] = $order->join([], fn($ret, $key, $value) => [$key, $value]);
        usort($records, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
        if ($order_direction === 'DESC') {
            $records = array_reverse($records);
        }
        return $records;
    }

    /**
     * @throws \arException
     */
    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return count($this->item_repository->getSubItemsForTable());
    }
}
