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
use ILIAS\UI\Component\Table\Presentation;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\Data\DataSize;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;

/**
 * Class ilResourceCollectionAsTable
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilResourceCollectionAsTable extends ilResourceCollectionAsComponent
{
    public function getComponent(): ILIAS\UI\Component\Component
    {
        return $this->ui_factory->table()->presentation(
            $this->definition->getComponentTitle(),
            $this->parent_gui->getViewControls(),
            $this->getRowMappingClosure()
        )->withData(
            $this->getSortedAndRangedData()
        );
    }


    private function getRowMappingClosure(): Closure
    {
        return function (
            PresentationRow $row,
            ResourceIdentification $resource_identification
        ): PresentationRow {
            $information = $this->getResourceInfo($resource_identification);

            $dropdown = $this->ui_factory->dropdown()->standard(
                $this->getButtons($resource_identification)
            );

            return $row->withHeadline($information->getTitle())
                ->withImportantFields([
                    $this->language->txt('create_date') => $information->getCreationDate()->format(
                        'Y-m-d H:i:s'
                    ),
                ])
                ->withContent(
                    $this->ui_factory->listing()->descriptive($this->getProperties($information))
                )->withAction($dropdown);
        };
    }
}
