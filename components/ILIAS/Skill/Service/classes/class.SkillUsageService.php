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
 ********************************************************************
 */

namespace ILIAS\Skill\Service;

use ILIAS\Skill\Usage;

/**
 * @author famula@leifos.de
 */
class SkillUsageService
{
    protected Usage\SkillUsageManager $usage_manager;

    public function __construct(SkillInternalService $internal_service)
    {
        $this->usage_manager = $internal_service->manager()->getUsageManager();
    }

    public function addUsage(int $obj_id, int $skill_id, int $tref_id): void
    {
        $this->usage_manager->addUsage($obj_id, $skill_id, $tref_id);
    }

    public function removeUsage(int $obj_id, int $skill_id, int $tref_id): void
    {
        $this->usage_manager->removeUsage($obj_id, $skill_id, $tref_id);
    }
}
