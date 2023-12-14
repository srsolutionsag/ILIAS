<?php

use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\UI\Component\Table as I;

/**
 * Class DataRetrieval
 *
 */
class DataRetrieval implements I\DataRetrieval
{
    use ILIAS\components\OrgUnit\ARHelper\DIC;
    protected \ilBiblFactoryFacade $facade;

    public function __construct(protected \ILIAS\UI\Factory $ui_factory, protected \ILIAS\UI\Renderer $ui_renderer, ilBiblFactoryFacade $facade)
    {
        $this->facade = $facade;
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
            $row_id = (string)$record['id'];
            $field = $this->facade->fieldFactory()->findById($record['field_id']);
            $record['field_id'] = $this->facade->translationFactory()->translate($field);
            $record['filter_type'] = $this->lng()->txt("filter_type_" . $record['filter_type']);
            yield $row_builder->buildDataRow($row_id, $record);
        }
    }

    protected function getRecords(Order $order): array
    {
        $info = new ilBiblTableQueryInfo();
        $info->setSortingColumn('id');

        $records = $this->facade->filterFactory()->filterItemsForTable($this->facade->iliasObjId(), $info);
        list($order_field, $order_direction) = $order->join([], fn($ret, $key, $value) => [$key, $value]);
        usort($records, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
        if ($order_direction === 'DESC') {
            $records = array_reverse($records);
        }
        return $records;
    }

    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return null;
    }
}
