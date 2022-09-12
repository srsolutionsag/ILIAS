<?php

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

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;
use ILIAS\ResourceStorage\Identification\ResourceCollectionIdentification;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\Data\DataSize;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;

/**
 * Class ilResourceCollectionGUI
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilResourceCollectionGUI implements ilCtrlBaseClassInterface, UploadHandler
{
    use Hasher;

    public const CMD_INDEX = 'index';
    public const CMD_UPLOAD = 'upload';
    public const CMD_REMOVE = 'remove';
    public const CMD_DOWNLOAD = 'download';
    public const CMD_CONFIRM_REMOVE = 'confirmRemove';
    public const CMD_INFO_FILE = 'infoFile';
    public const P_RESOURCE_ID = 'resource_id';
    public const P_PAGE = 'page';
    public const P_SORTATION = 'sort';
    public const BY_CREATION_DATE_DESC = 'by_creation_date_desc';
    public const BY_CREATION_DATE_ASC = 'by_creation_date_asc';
    public const BY_TITLE_DESC = 'by_title_desc';
    public const BY_TITLE_ASC = 'by_title_asc';
    public const BY_SIZE_DESC = 'by_size_desc';
    public const BY_SIZE_ASC = 'by_size_asc';
    public const P_MODE = 'mode';

    protected ilCtrlInterface $ctrl;
    protected ilLanguage $language;
    protected \ILIAS\UI\Renderer $ui_renderer;
    protected \ILIAS\UI\Factory $ui_factory;
    protected ilGlobalTemplateInterface $main_tpl;
    protected \ILIAS\ResourceStorage\Services $irss;
    protected \ILIAS\FileUpload\FileUpload $upload;
    protected \ILIAS\HTTP\Services $http;
    protected \ILIAS\Refinery\Factory $refinery;
    protected ilResourceCollectionViewDefinition $definition;
    protected \ILIAS\ResourceStorage\Collection\ResourceCollection $collection;
    protected \ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder $stakeholder;
    protected \ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper $query;
    protected array $components = [];

    final public function __construct(?ilResourceCollectionViewDefinition $definition = null)
    {
        global $DIC;
        // Services
        $this->irss = $DIC->resourceStorage();
        $this->ctrl = $DIC->ctrl();
        $this->language = $DIC->language();
        $this->language->loadLanguageModule('file');
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->upload = $DIC->upload();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->query = $DIC->http()->wrapper()->query();
        // Definition and Collection
        $this->definition = $definition ?? $this->buildDemoDefinition();
        $this->collection = $this->definition->getCollection();
        $this->stakeholder = $this->definition->getStakeholder();
    }

    public function getDefinition(): ilResourceCollectionViewDefinition
    {
        return $this->definition;
    }

    public function determinePage(): int
    {
        return $this->query->has(self::P_PAGE)
            ? $this->query->retrieve(self::P_PAGE, $this->refinery->kindlyTo()->int())
            : 0;
    }

    public function determineSortation(): string
    {
        return $this->query->has(self::P_SORTATION)
            ? $this->query->retrieve(self::P_SORTATION, $this->refinery->kindlyTo()->string())
            : self::BY_TITLE_ASC;
    }

    public function determineMode(): int
    {
        return $this->query->has(self::P_MODE)
            ? $this->query->retrieve(self::P_MODE, $this->refinery->kindlyTo()->int())
            : $this->definition->getMode();
    }

    final public function executeCommand(): void
    {
        $title = $this->definition->getViewTitle();
        if ($title !== null) {
            $this->main_tpl->setTitle($title);
        }
        $description = $this->definition->getViewDescription();
        if ($description !== null) {
            $this->main_tpl->setDescription($description);
        }
        $this->ctrl->saveParameter($this, self::P_SORTATION);
        $this->ctrl->saveParameter($this, self::P_PAGE);
        $this->ctrl->saveParameter($this, self::P_MODE);

        switch ($this->ctrl->getCmd(self::CMD_INDEX)) {
            case self::CMD_INDEX:
                $this->index();
                break;
            case self::CMD_UPLOAD:
                $this->upload();
                break;
            case self::CMD_REMOVE:
                $this->remove();
                break;
            case self::CMD_DOWNLOAD:
                $this->download();
                break;
        }
        $is_called_as_base = count($this->ctrl->getCallHistory()) === 1;
        if ($is_called_as_base) {
            $this->main_tpl->loadStandardTemplate();
            $this->main_tpl->printToStdout();
        }
    }

    public function getViewControls(): array
    {
        $view_controls = [];

        // Pagination
        $count = count($this->collection->getResourceIdentifications());
        if ($count > $this->definition->getItemsPerPage()) {
            $view_controls[] = $this->ui_factory->viewControl()->pagination()
                ->withTargetURL(
                    $this->ctrl->getLinkTarget($this, self::CMD_INDEX),
                    self::P_PAGE
                )
                ->withCurrentPage($this->determinePage())
                ->withPageSize($this->definition->getItemsPerPage())
                ->withTotalEntries($count)
                ->withMaxPaginationButtons(5);
        }

        // View Mode
        $base_link = $this->ctrl->getLinkTarget($this, self::CMD_INDEX);

        switch ($this->determineMode()) {
            case ilResourceCollectionViewDefinition::MODE_AS_TABLE:
                $active = 'Table';
                break;
            case ilResourceCollectionViewDefinition::MODE_AS_ITEMS:
                $active = 'Items';
                break;
            case ilResourceCollectionViewDefinition::MODE_AS_DECK:
                $active = 'Cards';
                break;
        }

        $view_controls[] = $this->ui_factory->viewControl()->mode([
            'Table' => $base_link . '&' . self::P_MODE . '=' . ilResourceCollectionViewDefinition::MODE_AS_TABLE,
            'Items' => $base_link . '&' . self::P_MODE . '=' . ilResourceCollectionViewDefinition::MODE_AS_ITEMS,
            'Cards' => $base_link . '&' . self::P_MODE . '=' . ilResourceCollectionViewDefinition::MODE_AS_DECK,
        ], 'Switch mode')->withActive($active);

        // Sortation
        $view_controls[] = $this->ui_factory->viewControl()->sortation([
            self::BY_TITLE_ASC => $this->language->txt(self::BY_TITLE_ASC),
            self::BY_TITLE_DESC => $this->language->txt(self::BY_TITLE_DESC),
            self::BY_CREATION_DATE_ASC => $this->language->txt(self::BY_CREATION_DATE_ASC),
            self::BY_CREATION_DATE_DESC => $this->language->txt(self::BY_CREATION_DATE_DESC),
            self::BY_SIZE_ASC => $this->language->txt(self::BY_SIZE_ASC),
            self::BY_SIZE_DESC => $this->language->txt(self::BY_SIZE_DESC),
        ])->withTargetURL(
            $this->ctrl->getLinkTarget($this, self::CMD_INDEX),
            self::P_SORTATION
        );

        return $view_controls;
    }


    public function getSortedCollection(): \ILIAS\ResourceStorage\Collection\ResourceCollection
    {
        $sorter = $this->irss->collection()->sort($this->collection);

        switch ($this->determineSortation()) {
            case ilResourceCollectionGUI::BY_TITLE_ASC:
            default:
                return $sorter->asc()->byTitle();
            case ilResourceCollectionGUI::BY_TITLE_DESC:
                return $sorter->desc()->byTitle();
            case ilResourceCollectionGUI::BY_CREATION_DATE_ASC:
                return $sorter->asc()->byCreationDate();
            case ilResourceCollectionGUI::BY_CREATION_DATE_DESC:
                return $sorter->desc()->byCreationDate();
            case ilResourceCollectionGUI::BY_SIZE_ASC:
                return $sorter->asc()->bySize();
            case ilResourceCollectionGUI::BY_SIZE_DESC:
                return $sorter->desc()->bySize();
        }
    }

    private function index(): void
    {
        // uploader
        if ($this->definition->isUploadEnabled()) {
            $this->main_tpl->addInlineCss(
                ".ui-dropzone-container {
                        height: 60px;
                        text-align: center;
                        line-height: 60px;
                        background-color: whitesmoke; 
                }"
            );
            $this->components[] = $this->ui_factory->dropzone()->file()->standard(
                $this,
                $this->ctrl->getLinkTarget($this, self::CMD_INDEX)
            )->withUploadButton(
                $this->ui_factory->button()->shy($this->language->txt('select_file'), '#')
            )->withMaxFiles(100);
        }


        // view vomponent
        switch ($this->determineMode()) {
            case ilResourceCollectionViewDefinition::MODE_AS_TABLE:
                $table = new ilResourceCollectionAsTable($this);
                $this->components[] = $this->ui_factory->legacy(
                    $this->ui_renderer->render($table->getComponent())
                ); // this is needed since modals are not rendered at the right time
                break;
            case ilResourceCollectionViewDefinition::MODE_AS_ITEMS:
                $items = new ilResourceCollectionAsItems($this);
                $this->components[] = $items->getComponent();
                break;
            case ilResourceCollectionViewDefinition::MODE_AS_DECK:
                $items = new ilResourceCollectionAsDeck($this);
                $this->components = array_merge($this->components, $this->getViewControls());
                $this->components[] = $items->getComponent();
                break;
        }

        $this->main_tpl->setContent(
            $this->ui_renderer->render($this->components)
        );
    }

    private function upload(): void
    {
        $this->upload->process();
        $result = array_values($this->upload->getResults())[0];
        if ($result->isOK()) {
            $id = $this->irss->manage()->upload($result, $this->stakeholder);
            $this->collection->add($id);
            $revision = $this->irss->manage()->getResource($id)->getCurrentRevision();
        }
        $this->irss->collection()->store($this->collection);
        $upload_result = new BasicHandlerResult(
            self::P_RESOURCE_ID,
            BasicHandlerResult::STATUS_OK,
            $id->serialize(),
            ''
        );
        $response = $this->http->response()->withBody(Streams::ofString(json_encode($upload_result)));
        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
    }


    private function download(): void
    {
        $rid = $this->getResourceIdFromRequest();
        if ($rid === null || !$this->collection->isIn($rid)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->language->txt('msg_no_perm_read'), true);
            $this->ctrl->redirect($this, self::CMD_INDEX);
            return;
        }
        $this->irss->consume()->download($rid)->run();
    }

    private function remove(): void
    {
        $rid = $this->getResourceIdFromRequest();
        if ($rid === null || !$this->collection->isIn($rid)) {
            $this->main_tpl->setOnScreenMessage('failure', $this->language->txt('msg_no_perm_read'), true);
            $this->ctrl->redirect($this, self::CMD_INDEX);
            return;
        }
        $this->irss->manage()->remove($rid, $this->stakeholder);
        $this->main_tpl->setOnScreenMessage('success', $this->language->txt('rid_deleted'), true);
        $this->ctrl->redirect($this, self::CMD_INDEX);
    }

    private function getResourceIdFromRequest(): ?ResourceIdentification
    {
        $wrapper = $this->http->wrapper();

        $rid = $wrapper->query()->has(self::P_RESOURCE_ID) ? $wrapper->query()->retrieve(
            self::P_RESOURCE_ID,
            $this->refinery->to()->string()
        ) : ($wrapper->post()->has(self::P_RESOURCE_ID)
            ? $wrapper->post()->retrieve(self::P_RESOURCE_ID, $this->refinery->to()->string())
            : null);

        if ($rid === null) {
            return null;
        }

        return $this->irss->manage()->find($rid);
    }

    public function getButtonsForResource(ResourceIdentification $i): array
    {
        $this->ctrl->setParameter(
            $this,
            self::P_RESOURCE_ID,
            $i->serialize()
        );

        // Delete
        $confirmation_modal = $this->getRemoveConfirmationModal($i);
        $delete_button = $this->ui_factory->button()->shy(
            $this->language->txt('delete'),
            ''
        )->withOnClick($confirmation_modal->getShowSignal());

        $this->components[] = $confirmation_modal;

        // Download
        $download_button = $this->ui_factory->button()->shy(
            $this->language->txt('download'),
            $this->ctrl->getLinkTarget(
                $this,
                self::CMD_DOWNLOAD
            )
        );

        return array_merge([
            $download_button,
            $delete_button,
        ], $this->definition->getAdditionalButtons($i, $this));
    }


    public function getRemoveConfirmationModal(ResourceIdentification $identification): Modal
    {
        $action = $this->ctrl->getLinkTarget($this, self::CMD_REMOVE);
        return $this->ui_factory->modal()->interruptive(
            $this->language->txt(self::CMD_REMOVE),
            $this->language->txt('confirm_delete'),
            $action
        )->withAffectedItems([
            $this->ui_factory->modal()->interruptiveItem(
                $identification->serialize(),
                $this->irss->manage()->getResource($identification)->getCurrentRevision()->getInformation()->getTitle(),
            )
        ]);
    }


    //
    // UPLOAD HANDLER
    //
    public function getFileIdentifierParameterName(): string
    {
        return self::P_RESOURCE_ID;
    }

    public function getUploadURL(): string
    {
        return $this->ctrl->getLinkTarget($this, self::CMD_UPLOAD);
    }

    public function getFileRemovalURL(): string
    {
        return $this->ctrl->getLinkTarget($this, self::CMD_REMOVE);
    }

    public function getExistingFileInfoURL(): string
    {
        return $this->ctrl->getLinkTarget($this, self::CMD_INFO_FILE);
    }

    public function getInfoForExistingFiles(array $file_ids): array
    {
        return [];
    }

    public function getInfoResult(string $identifier): ?FileInfoResult
    {
        return null;
    }

    private function buildDemoDefinition(): ilResourceCollectionViewDefinition
    {
        global $DIC;
        $collection = $this->irss->collection()->get(
            new ResourceCollectionIdentification("1cffe9d5-a212-46de-a4e6-fc02e4c982a1")
        );
        return new ilResourceCollectionViewDefinition(
            $collection,
            new ilTemporaryStakeholder(),
            'Collection of Files',
            'IRSS Resource Collection Demo',
            '',
            round(count($collection->getResourceIdentifications()) / 3, 0, PHP_ROUND_HALF_DOWN)
        );
    }
}
