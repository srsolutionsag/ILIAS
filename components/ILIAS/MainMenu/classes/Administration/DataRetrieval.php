<?php

namespace ILIAS\MainMenu\Administration;

use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\UI\Component\Table as I;

/**
 *
 */
class DataRetrieval implements I\DataRetrieval
{
    private \ilLanguage $lng;

    public function __construct(
        protected \ilMMItemFacade $facade
    ) {
        global $DIC;
        $this->lng = $DIC['lng'];
    }

    public function getRows(
        I\DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        $records = $this->getRecords($order);
        foreach ($records as $idx => $record) {
            $row_id = (string) $record['id'];
            //$field = $this->facade->getItem fieldFactory()->findById($record['field_id']);
            //$record['field_id'] = $this->facade->translationFactory()->translate($field);
            $record['filter_type'] = $this->lng->txt("filter_type_" . $record['filter_type']);
            yield $row_builder->buildDataRow($row_id, $record);
        }
    }

    protected function getRecords(Order $order): array
    {
        //$this->info->setSortingColumn('id');

        $records = $this->facade->filterFactory()->filterItemsForTable($this->facade->iliasObjId(), $this->info);
        [$order_field, $order_direction] = $order->join([], fn($ret, $key, $value) => [$key, $value]);
        //usort($records, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
        if ($order_direction === 'DESC') {
        //    $records = array_reverse($records);
        }
        //return $records;
        return [];
    }

    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return null;
            //count($this->facade->filterFactory()->getAllForObjectId($this->facade->iliasObjId()));
    }
}
