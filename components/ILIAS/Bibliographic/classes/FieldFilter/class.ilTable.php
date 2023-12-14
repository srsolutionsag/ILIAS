<?php

use ILIAS\UI\URLBuilder;

/**
 * Class ilTable
 *
 */
class ilTable
{
    use \ILIAS\Modules\OrgUnit\ARHelper\DIC;
    protected \ILIAS\UI\Component\Modal\RoundTrip $modal;
    protected array $interruptive_modals = [];
    protected array $filter = [];
    protected ILIAS\UI\Implementation\Component\Table\Data $table;
    protected \ilBiblFactoryFacade $facade;


    /**
     * ilTable constructor.
     */

    public function __construct(?object $a_parent_obj, ilBiblFactoryFacade $facade)
    {
        $this->facade = $facade;
        $this->parent_obj = $a_parent_obj;
        global $DIC;
        $f = $DIC['ui.factory'];
        $r = $DIC['ui.renderer'];
        $df = new \ILIAS\Data\Factory();
        $refinery = $DIC['refinery'];
        $here_uri = $df->uri($DIC->http()->request()->getUri()->__toString());
        $this->modal = $f->modal()->roundtrip('---', [$f->legacy('')])->withAsyncRenderUrl($this->ctrl()->getLinkTarget($this->parent_obj, ilBiblFieldFilterGUI::CMD_EDIT));


        $columns = [
            'field_id' => $f->table()->column()->text($this->lng()->txt('field')),
            'filter_type' => $f->table()->column()->text($this->lng()->txt('filter_type'))
        ];

        $url_builder = new URLBuilder($here_uri);

        //these are the query parameters this instance is controlling
        $query_params_namespace = ['bibliographic', 'filter'];
        list($url_builder, $id_token, $action_token) = $url_builder->acquireParameters(
            $query_params_namespace,
            "row_id",
            "table_action"
        );

        $actions = [
            'edit' => $f->table()->action()->single(
                $this->lng()->txt("edit"),
                $url_builder->withParameter($action_token, $this->ctrl()->getLinkTargetByClass(ilBiblFieldFilterGUI::class, ilBiblFieldFilterGUI::CMD_EDIT)),
                $id_token
            ),
            'delete' => $f->table()->action()->single(
                $this->lng()->txt("delete"),
                $url_builder->withParameter($action_token, ilBiblFieldFilterGUI::CMD_RENDER_INTERRUPTIVE),
                $id_token
            )->withAsync(),
        ];

        $data_retrieval = new DataRetrieval($f, $r, $facade);

        $this->table = $f->table()->data("", $columns, $data_retrieval)->withActions($actions);

        //render table and results
        $result = [$this->table];
        $query = $DIC->http()->wrapper()->query();
        if ($query->has($action_token->getName())) {
            $action = $query->retrieve($action_token->getName(), $refinery->to()->string());
            $ids = $query->retrieve($id_token->getName(), $refinery->custom()->transformation(fn($v) => $v));

            if ($action === 'delete') {
                $items = [];
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    $items[] = $f->modal()->interruptiveItem()->keyValue($id, $id_token->getName(), $id);
                }

                $delete_modal = $f->modal()->interruptive(
                    '',
                    '',
                    ''
                )->withAffectedItems($items)
                 ->withAsyncRenderUrl($this->ctrl()->getLinkTargetByClass(ilBiblFieldFilterGUI::class, ilBiblFieldFilterGUI::CMD_RENDER_INTERRUPTIVE, '', true));
                $this->interruptive_modals[] = $delete_modal;
                $delete_modal->getShowSignal();
                $result[] = $items;
            } else {
                $this->ctrl()->getLinkTargetByClass(ilBiblFieldFilterGUI::class, ilBiblFieldFilterGUI::CMD_EDIT);
            }
        }
        return $r->render($result);
    }

    /**
     * @return \ILIAS\UI\Component\Modal\Interruptive[]
     */
    protected function getInterruptiveModals(): array
    {
        return $this->interruptive_modals;
    }


    /**
     * @inheritDoc
     */

    public function getHTML(): string
    {
        global $DIC;

        $r = $DIC['ui.renderer'];
        $r->render($this->table);
        $table = $r->render($this->table);
        $modals = $this->dic()->ui()->renderer()->render($this->getInterruptiveModals());

        return $table . $modals;
    }
}
