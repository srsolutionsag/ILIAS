<?php

declare(strict_types=1);

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

namespace ILIAS\ResourceStorage\Collection\Sorter;

use ILIAS\ResourceStorage\Collection\ResourceCollection;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

/**
 * Class ByCreationDate
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class ByCreationDate extends AbstractBaseSorter implements CollectionSorter
{
    protected function sortResourceIdentification(array $identifications): array
    {
        usort($identifications, function (ResourceIdentification $a, ResourceIdentification $b) {
            $a_creation_date = $this->resource_builder->get($a)->getCurrentRevision()->getInformation()->getCreationDate();
            $b_creation_date = $this->resource_builder->get($b)->getCurrentRevision()->getInformation()->getCreationDate();
            return $a_creation_date->diff($b_creation_date)->invert;
        });
        return $identifications;
    }
}
