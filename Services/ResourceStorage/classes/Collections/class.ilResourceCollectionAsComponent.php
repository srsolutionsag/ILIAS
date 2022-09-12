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
use ILIAS\ResourceStorage\Information\Information;
use ILIAS\UI\Component\Table\Presentation;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\Data\DataSize;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;

/**
 * Class ilResourceCollectionAsComponent
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class ilResourceCollectionAsComponent
{
    use Hasher;

    protected ilResourceCollectionViewDefinition $definition;
    protected ilResourceCollectionGUI $parent_gui;
    protected \ILIAS\ResourceStorage\Services $irss;
    protected \ILIAS\UI\Factory $ui_factory;
    protected \ILIAS\UI\Renderer $ui_renderer;
    protected ilLanguage $language;
    protected ilCtrlInterface $ctrl;
    protected \ILIAS\HTTP\Wrapper\WrapperFactory $wrapper;


    public function __construct(
        ilResourceCollectionGUI $parent_gui,
    ) {
        global $DIC;
        $this->parent_gui = $parent_gui;
        $this->definition = $parent_gui->getDefinition();
        $this->irss = $DIC->resourceStorage();
        $this->ctrl = $DIC->ctrl();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->language = $DIC->language();
        $this->language->loadLanguageModule('file');
    }

    abstract public function getComponent(): ILIAS\UI\Component\Component;

    protected function getButtons(ResourceIdentification $i): array
    {
        return $this->parent_gui->getButtonsForResource($i);
    }


    protected function getSortedAndRangedData(int $factor = 1): array
    {
        return $this->irss->collection()->rangeAsArray(
            $this->parent_gui->getSortedCollection(),
            $this->parent_gui->determinePage() * $this->definition->getItemsPerPage() * $factor,
            $this->definition->getItemsPerPage() * $factor
        );
    }

    protected function getResourceInfo(ResourceIdentification $resource_identification): Information
    {
        $resource = $this->irss->manage()->getResource($resource_identification);
        $current_revision = $resource->getCurrentRevision();
        return $current_revision->getInformation();
    }

    protected function getSize(Information $information): DataSize
    {
        $size = $information->getSize();
        switch (true) {
            case $size > 1024 * 1024 * 1024:
                $unit = DataSize::GB;
                break;
            case $size > 1024 * 1024:
                $unit = DataSize::MB;
                break;
            case $size > 1024:
                $unit = DataSize::KB;
                break;
            default:
                $unit = DataSize::Byte;
                break;
        }

        return new DataSize($size, $unit);
    }

    protected function getProperties(\ILIAS\ResourceStorage\Information\Information $information): array
    {
        return [
            $this->language->txt('size') => $this->getSize($information)->__toString(),
            $this->language->txt('type') => $information->getMimeType(),
            $this->language->txt('create_date') => $information->getCreationDate()->format(
                'Y-m-d H:i:s'
            )
        ];
    }

    protected function getImage(ResourceIdentification $resource_identification): \ILIAS\UI\Component\Image\Image
    {
        return $this->ui_factory->image()->standard(
            "./templates/default/images/icon_file.svg",
            $this->getResourceInfo($resource_identification)->getTitle()
        );
    }
}
