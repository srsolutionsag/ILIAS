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

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package components\ILIAS/Test(QuestionPool)
 */
class ilAssQuestionAssignedSkillList implements Iterator
{
    protected array $skills = [];

    public function addSkill(int $skill_base_id, int $skill_ref_id): void
    {
        $this->skills[] = "{$skill_base_id}:{$skill_ref_id}";
    }

    public function skillsExist(): bool
    {
        return (bool) count($this->skills);
    }

    public function current(): ?array
    {
        $current = current($this->skills);
        return $current !== false ? $current : null;
    }

    public function next(): void
    {
        next($this->skills);
    }

    public function key(): ?int
    {
        return key($this->skills);
    }

    public function valid(): bool
    {
        $res = key($this->skills);
        return $res !== null;
    }

    public function rewind(): void
    {
        reset($this->skills);
    }

    public function sleep(): array
    {
        return ['skills'];
    }

    public function wakeup(): void
    {
        // TODO: Implement __wakeup() method.
    }
}
