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
 * Class ilResourceCollectionAsDeck
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilResourceCollectionAsDeck extends ilResourceCollectionAsComponent
{
    public function getComponent(): ILIAS\UI\Component\Component
    {
        return $this->ui_factory->deck($this->getCards())->withSmallCardsSize();
    }

    protected function getCards(): array
    {
        return array_map(function (ResourceIdentification $identification): Card {
            $info = $this->getResourceInfo($identification);
            return $this->ui_factory->card()->repositoryObject(
                $info->getTitle(),
                $this->getImage($identification)
            )->withActions(
                $this->ui_factory->dropdown()->standard(
                    $this->getButtons($identification)
                )
            )->withSections(array(
                $this->ui_factory->listing()->descriptive($this->getProperties($info)),
            ));
        }, $this->getSortedAndRangedData(6));
    }
}
