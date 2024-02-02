<?php

namespace ILIAS\MainMenu\Administration;

use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\UI\Component\Table as I;
use ilMMAbstractItemGUI;
use ilMMItemRepository;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;
use ilObjMainMenuAccess;

/**
 *
 */
class DataRetrievalTopItems implements I\DataRetrieval
{
    use Hasher;
    private \ilLanguage $lng;
    private ilMMItemRepository $item_repository;
    private ilObjMainMenuAccess $access;
    private \ILIAS\UI\Renderer $ui_renderer;
    private \ILIAS\UI\Factory $ui_factory;

    public function __construct(
        ilMMItemRepository $item_repository,
        ilObjMainMenuAccess $access
    ) {
        global $DIC;
        $this->lng = $DIC['lng'];
        $this->access = $access;
        $this->item_repository = $item_repository;
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->ui_factory = $DIC->ui()->factory();
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
        $records = $this->getRecords($order);
        foreach ($records as $idx => $record) {
            $item_facade = $this->item_repository->repository()->getItemFacade($DIC->globalScreen()->identification()->fromSerializedIdentification($record['identification']));
            $record['identifier'] = ilMMAbstractItemGUI::IDENTIFIER;
            $record['id'] = $this->hash($item_facade->getId());
            $record['native_id'] = $item_facade->getId();
            $record['title'] = $item_facade->getDefaultTitle();
            $record['subentries'] = $item_facade->getAmountOfChildren();
            $record['type'] = $item_facade->getTypeForPresentation();
            $record['css_id'] = "mm_" . $item_facade->identification()->getInternalIdentifier();
            $record['provider'] = $item_facade->getProviderNameForPresentation();
            $row_id = (string) $record['id'];

            $icon_active = $this->ui_renderer->render($this->ui_factory->symbol()->icon()->custom(
                $item_facade->isActivated() ? \ilUtil::getImagePath('standard/icon_ok.svg') : \ilUtil::getImagePath('standard/icon_not_ok.svg'),
                $item_facade->isActivated() ? $this->lng->txt('active') : $this->lng->txt('inactive')
            ));

            $record['active'] = $icon_active;


            yield $row_builder->buildDataRow($row_id, $record)
                ->withDisabledAction("edit", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("translate", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("delete", !$this->access->hasUserPermissionTo('write'))
                ->withDisabledAction("move", !$this->access->hasUserPermissionTo('write'));
        }
    }

    protected function getRecords(Order $order): array
    {
        $records = $this->item_repository->getTopItems();
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
        return count($this->item_repository->getTopItems());
    }
}
