<?php

namespace ILIAS\MainMenu\Administration;

use ILIAS\UI\URLBuilder;
use ILIAS\Data\URI;
use ILIAS\UI\URLBuilderToken;
use ilMMAbstractItemGUI;
use ilMMItemRepository;
use ilObjMainMenuAccess;

/**
 *
 */
class TableSubItems
{
    private \ILIAS\UI\Factory $ui_factory;
    private \ILIAS\UI\Renderer $ui_renderer;
    private \ilCtrlInterface $ctrl;
    private \ilLanguage $lng;
    private URLBuilder $url_builder;
    private URLBuilderToken $id_token;

    private ilObjMainMenuAccess $access;

    protected array $components = [];

    public function __construct(
        private \ilMMSubItemGUI $calling_gui,
        ilMMItemRepository $item_repository,
        ilObjMainMenuAccess $access
    ) {
        global $DIC;
        $this->ui_factory = $DIC['ui.factory'];
        $this->ui_renderer = $DIC['ui.renderer'];
        $this->ctrl = $DIC['ilCtrl'];
        $this->lng = $DIC['lng'];
        $this->access = $access;

        $this->url_builder = $this->initURIBuilder();
        $columns = $this->initColumns();
        $actions = $this->initActions();
        $data_retrieval = new DataRetrievalSubItems(
            $item_repository,
            $access
        );

        $this->components[] = $this->ui_factory->table()->data(
            $this->lng->txt(''),
            $columns,
            $data_retrieval
        )->withActions($actions)->withRequest(
            $DIC->http()->request()
        );
    }

    private function initURIBuilder(): URLBuilder
    {
        $url_builder = new URLBuilder(
            $this->getURI(\ilMMTopItemGUI::CMD_VIEW_TOP_ITEMS)
        );

        // these are the query parameters this instance is controlling
        $query_params_namespace = ['mm', 'sub_item'];
        [$url_builder, $this->id_token] = $url_builder->acquireParameters(
            $query_params_namespace,
            ilMMAbstractItemGUI::IDENTIFIER
        );

        return $url_builder;
    }


    protected function initColumns(): array
    {
        return [
            //'parent' => $this->ui_factory->table()->column()->text($this->lng->txt('sub_parent')),
            //'title' => $this->ui_factory->table()->column()->text($this->lng->txt('sub_title')),
            //'type' => $this->ui_factory->table()->column()->text($this->lng->txt('sub_type')),
            'active' => $this->ui_factory->table()->column()->text($this->lng->txt('sub_active')),
            'status' => $this->ui_factory->table()->column()->text($this->lng->txt('sub_status')),
            'provider' => $this->ui_factory->table()->column()->text($this->lng->txt('sub_provider')),
        ];
    }

    protected function initActions(): array
    {
        return [
        'edit' => $this->ui_factory->table()->action()->single(
            $this->lng->txt(\ilMMSubItemGUI::CMD_EDIT),
            $this->url_builder->withURI($this->getURI(\ilMMSubItemGUI::CMD_EDIT)),
            $this->id_token
        ),
        'translate' => $this->ui_factory->table()->action()->single(
            $this->lng->txt(\ilMMSubItemGUI::CMD_TRANSLATE),
            $this->url_builder->withURI($this->getURI(\ilMMItemTranslationGUI::CMD_DEFAULT)),
            $this->id_token
        ),
        'delete' => $this->ui_factory->table()->action()->standard(
            $this->lng->txt(\ilMMSubItemGUI::CMD_DELETE),
            $this->url_builder->withURI($this->getURI(\ilMMSubItemGUI::CMD_DELETE)),
            $this->id_token
        ),
        'move' => $this->ui_factory->table()->action()->single(
            $this->lng->txt(\ilMMSubItemGUI::CMD_MOVE . '_to_top_item'),
            $this->url_builder->withURI($this->getURI(\ilMMSubItemGUI::CMD_VIEW_SUB_ITEMS)),
            $this->id_token
        )
        ];
    }

    /**
     * @description Unfortunately, I have not yet found an easier way to generate this URI. However, it is important
     * that it points to the calling-gui
     */
    protected function getURI(string $command): URI
    {
        return new URI(
            ILIAS_HTTP_PATH . "/" . $this->ctrl->getLinkTarget(
                $this->calling_gui,
                $command
            )
        );
    }

    public function getHTML(): string
    {
        return $this->ui_renderer->render($this->components);
    }

    public function getUrlBuilder(): URLBuilder
    {
        return $this->url_builder;
    }

    public function getIdToken(): URLBuilderToken
    {
        return $this->id_token;
    }
}
