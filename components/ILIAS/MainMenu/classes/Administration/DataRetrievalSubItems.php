<?php

namespace ILIAS\MainMenu\Administration;

use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\hasTitle;
use ILIAS\UI\Component\Table as I;
use ilMMAbstractItemGUI;
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
            $record['identifier'] = $record['identification'];
            $record['id'] = $this->hash($item_facade->getId());
            $record['native_id'] = $item_facade->getId();
            $record['title'] = $item_facade->getDefaultTitle();
            //$record['parent'] = $this->$item_facade;
            $record['type'] = $item_facade->getTypeForPresentation();
            $record['status'] = $item_facade->getStatus();
            $record['provider'] = $item_facade->getProviderNameForPresentation();
            $row_id = (string) $record['id'];

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

            yield $row_builder->buildDataRow($row_id, $record)
                ->withDisabledAction("edit", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("translate", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("delete", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("move", !$this->access->hasUserPermissionTo('write'));
        }
    }

    protected function getRecords(Order $order): array
    {
        $records = $this->item_repository->getSubItemsForTable();
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
