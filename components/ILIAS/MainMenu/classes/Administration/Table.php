<?php

namespace ILIAS\MainMenu\Administration;

use ILIAS\UI\URLBuilder;
use ILIAS\Data\URI;
use ILIAS\UI\URLBuilderToken;
use ilMMItemRepository;

/**
 *
 */
class Table
{
    private \ILIAS\UI\Factory $ui_factory;
    private \ILIAS\UI\Renderer $ui_renderer;
    private \ilCtrlInterface $ctrl;
    private \ilLanguage $lng;
    private URLBuilder $url_builder;
    private URLBuilderToken $id_token;

    protected array $components = [];

    public function __construct(
        private \ilMMTopItemGUI $calling_gui, ilMMItemRepository $item_repository,
    ) {
        global $DIC;
        $this->ui_factory = $DIC['ui.factory'];
        $this->ui_renderer = $DIC['ui.renderer'];
        $this->ctrl = $DIC['ilCtrl'];
        $this->lng = $DIC['lng'];

        $this->url_builder = $this->initURIBuilder();
        $columns = $this->initColumns();
        $actions = $this->initActions();
        $data_retrieval = new DataRetrieval(
            $item_repository
        );

        $this->components[] = $this->ui_factory->table()->data(
            $this->lng->txt('top_item'),
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
        $query_params_namespace = ['mm', 'top_item'];
        [$url_builder, $this->id_token] = $url_builder->acquireParameters(
            $query_params_namespace,
            "row_id"
        );

        return $url_builder;
    }


    protected function initColumns(): array
    {
        return [
            'title' => $this->ui_factory->table()->column()->text($this->lng->txt('topitem_title')),
            'active' => $this->ui_factory->table()->column()->text($this->lng->txt('topitem_active')),
            'subentries' => $this->ui_factory->table()->column()->text($this->lng->txt('topitem_subentries')),
            'css_id' => $this->ui_factory->table()->column()->text($this->lng->txt('topitem_css_id')),
            'type' => $this->ui_factory->table()->column()->text($this->lng->txt('topitem_type')),
            'provider' => $this->ui_factory->table()->column()->text($this->lng->txt('topitem_provider'))   ,
        ];
    }

    protected function initActions(): array
    {
        return [
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt("edit"),
                $this->url_builder->withURI($this->getURI(\ilMMTopItemGUI::CMD_EDIT)),
                $this->id_token
            ),
            'translate' => $this->ui_factory->table()->action()->single(
                $this->lng->txt("translate"),
                $this->url_builder->withURI($this->getURI(\ilMMTopItemGUI::CMD_TRANSLATE)),
                $this->id_token
            ),
            'move' => $this->ui_factory->table()->action()->standard(
                $this->lng->txt("move"),
                $this->url_builder->withURI($this->getURI(\ilMMTopItemGUI::CMD_MOVE)),
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
                $this->calling_gui, $command
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
