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

declare(strict_types=1);

namespace ILIAS\Export\ImportHandler\I\File\XML\Manifest;

use Countable;
use ILIAS\Export\ImportHandler\File\XML\Manifest\ilExportObjectType;
use ILIAS\Export\ImportHandler\I\File\XML\Manifest\ilHandlerInterface as ilManifestXMLFileHandlerInterface;
use ILIAS\Export\ImportStatus\I\ilCollectionInterface as ilImportStatusHandlerCollectionInterface;
use Iterator;

interface ilHandlerCollectionInterface extends Iterator, Countable
{
    public function withMerged(ilHandlerCollectionInterface $other): ilHandlerCollectionInterface;

    public function withElement(ilManifestXMLFileHandlerInterface $element): ilHandlerCollectionInterface;

    public function validateElements(): ilImportStatusHandlerCollectionInterface;

    public function containsExportObjectType(ilExportObjectType $type): bool;

    public function findNextFiles(): ilHandlerCollectioNinterface;

    /**
     * @return ilManifestXMLFileHandlerInterface[]
     */
    public function toArray(): array;

    public function current(): ilManifestXMLFileHandlerInterface;

    public function next(): void;

    public function key(): int;

    public function valid(): bool;

    public function rewind(): void;
}
