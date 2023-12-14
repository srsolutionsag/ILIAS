<?php

use ILIAS\UI\URLBuilder;

/**
 * Class ilTable
 *
 */
class ilTable1
{
    use \ILIAS\Modules\OrgUnit\ARHelper\DIC;
    protected array $filter = [];
    protected ILIAS\UI\Implementation\Component\Table\Data $table;
    protected \ilBiblAdminFactoryFacadeInterface $facade;


    /**
     * ilTable constructor.
     */

    public function __construct(?object $a_parent_obj, ilBiblAdminFactoryFacadeInterface $facade)
    {
        $this->facade = $facade;
        $this->parent_obj = $a_parent_obj;
        global $DIC;
        $f = $DIC['ui.factory'];
        $r = $DIC['ui.renderer'];
        $df = new \ILIAS\Data\Factory();
        $refinery = $DIC['refinery'];
        $here_uri = $df->uri($DIC->http()->request()->getUri()->__toString());

        $columns = [
            //'position' => $f->table()->column()->text($this->lng()->txt('order')),
            'identifier' => $f->table()->column()->text($this->lng()->txt('identifier')),
            'data_type' => $f->table()->column()->text($this->lng()->txt('translation')),
            'is_standard_field' => $f->table()->column()->text($this->lng()->txt('standard'))
        ];

        $url_builder = new URLBuilder($here_uri);

        //these are the query parameters this instance is controlling
        $query_params_namespace = ['bibliographic', 'admin'];
        list($url_builder, $id_token, $action_token) = $url_builder->acquireParameters(
            $query_params_namespace,
            "row_id",
            "table_action"
        );

        $actions = [
            'translate' => $f->table()->action()->single(
                'Translate',
                $url_builder->withParameter($action_token, "translate"),
                $id_token
            )
        ];

        $data_retrieval = new DataRetrieval1($f, $r, $facade);

        if ($this->parent_obj->checkPermissionBoolAndReturn('write')) {
            //$this->addCommandButton(ilBiblAdminFieldGUI::CMD_SAVE, $this->lng()->txt("save"));
            $this->table = $f->table()->data("", $columns, $data_retrieval)->withActions($actions);
        } else {
            $this->table = $f->table()->data("", $columns, $data_retrieval);
        }

        $result = [$this->table];
        $query = $DIC->http()->wrapper()->query();
        if ($query->has($action_token->getName())) {
            $action = $query->retrieve($action_token->getName(), $refinery->to()->string());
            $ids = $query->retrieve($id_token->getName(), $refinery->custom()->transformation(fn($v) => $v));

            if ($action === 'translate') {
                $this->ctrl()->getLinkTargetByClass(ilBiblTranslationGUI::class, ilBiblTranslationGUI::CMD_DEFAULT);
            }
        }

        return $r->render($result);
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

        return $table;
    }
}
