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
use ILIAS\ResourceStorage\Collection\ResourceCollection;
use ILIAS\ResourceStorage\Identification\ResourceCollectionIdentification;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\Data\DataSize;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;

/**
 * Class ilResourceCollectionViewDefinition
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilResourceCollectionViewDefinition
{
    public const MODE_AS_TABLE = 1;
    public const MODE_AS_ITEMS = 2;
    public const MODE_AS_DECK = 3;
    private ResourceCollection $collection;
    private ?string $view_title = null;
    private ?string $view_description = null;
    private string $component_title;
    private int $mode = self::MODE_AS_TABLE;
    private int $items_per_page = 10;
    private ResourceStakeholder $stakeholder;
    private bool $enable_upload = true;


    public function __construct(
        ResourceCollection $collection,
        ResourceStakeholder $stakeholder,
        string $component_title,
        ?string $view_title = null,
        ?string $view_description = null,
        int $items_per_page = 50
    ) {
        $this->collection = $collection;
        $this->view_title = $view_title;
        $this->view_description = $view_description;
        $this->component_title = $component_title;
        $this->mode = self::MODE_AS_TABLE;
        $this->items_per_page = $items_per_page;
        $this->stakeholder = $stakeholder;
        $this->enable_upload = true;
    }


    final public function getCollection(): ResourceCollection
    {
        return $this->collection;
    }

    public function getViewTitle(): ?string
    {
        return $this->view_title;
    }


    public function getViewDescription(): ?string
    {
        return $this->view_description;
    }


    public function getComponentTitle(): string
    {
        return $this->component_title;
    }


    final public function getMode(): int
    {
        return $this->mode;
    }


    public function getItemsPerPage(): int
    {
        return $this->items_per_page;
    }

    public function getStakeholder(): ResourceStakeholder
    {
        return $this->stakeholder;
    }


    public function isUploadEnabled(): bool
    {
        return $this->enable_upload;
    }

    public function getAdditionalButtons(
        ResourceIdentification $i,
        ilResourceCollectionGUI $parent_gui
    ): array {
        return [

        ];
    }
}
