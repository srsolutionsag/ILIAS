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

class ilBuddySystemRequestedRelationStateTableFilterMapper extends ilAbstractBuddySystemRelationStateTableFilterMapper
{
    public function optionsForState(): array
    {
        return [
            $this->state::class . '_a' => $this->lng->txt(
                'buddy_bs_state_' . strtolower($this->state->getName()) . '_filter_a'
            ),
            $this->state::class . '_p' => $this->lng->txt(
                'buddy_bs_state_' . strtolower($this->state->getName()) . '_filter_p'
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filterMatchesRelation(string $filter_key, ilBuddySystemRelation $relation): bool
    {
        return (
            (strtolower($filter_key) === strtolower($this->state::class . '_a') && $relation->isOwnedByActor()) ||
            (strtolower($filter_key) === strtolower($this->state::class . '_p') && !$relation->isOwnedByActor())
        );
    }
}
