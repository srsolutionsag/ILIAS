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

use ILIAS\Data\Color;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;
use ILIAS\ResourceStorage\Identification\ResourceCollectionIdentification;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\UI\Component\Card\Card;
use ILIAS\UI\Component\Item\Group;
use ILIAS\UI\Component\Item\Item;
use ILIAS\UI\Component\Item\Standard;
use ILIAS\UI\Component\Table\Presentation;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\Data\DataSize;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;

/**
 * Class ilResourceCollectionAsItems
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilResourceCollectionAsItems extends ilResourceCollectionAsComponent
{
    public function getComponent(): ILIAS\UI\Component\Component
    {
        return $this->ui_factory->panel()->secondary()->legacy(
            $this->definition->getComponentTitle(),
            $this->ui_factory->legacy($this->ui_renderer->render($this->getItems()))
        )->withViewControls($this->parent_gui->getViewControls());
    }


    /**
     * @return Standard[]
     */
    private function getItems(): array
    {
        return array_map(
            function (ResourceIdentification $resource_identification): Standard {
                $information = $this->getResourceInfo($resource_identification);

                $description = $this->language->txt('create_date')
                    . ': ' . $information->getCreationDate()->format(
                        'Y-m-d H:i:s'
                    );


                return $this->ui_factory->item()->standard($information->getTitle())
                    ->withActions(
                        $this->ui_factory->dropdown()->standard(
                            $this->getButtons($resource_identification)
                        )
                    )
                    ->withLeadImage($this->getImage($resource_identification))
                    ->withProperties($this->getProperties($information));
            },
            $this->getSortedAndRangedData()
        );
    }
}
