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

/**
* Class ilStudyProgrammeAutoMembershipSource
*
* @author: Nils Haagen <nils.haagen@concepts-and-training.de>
*/
class ilStudyProgrammeAutoMembershipSource
{
    public const TYPE_ROLE = 'role';
    public const TYPE_GROUP = 'grp';
    public const TYPE_COURSE = 'crs';
    public const TYPE_ORGU = 'orgu';

    public const SOURCE_MAPPING = [
        self::TYPE_ROLE => ilPRGAssignment::AUTO_ASSIGNED_BY_ROLE,
        self::TYPE_GROUP => ilPRGAssignment::AUTO_ASSIGNED_BY_GROUP,
        self::TYPE_COURSE => ilPRGAssignment::AUTO_ASSIGNED_BY_COURSE,
        self::TYPE_ORGU => ilPRGAssignment::AUTO_ASSIGNED_BY_ORGU
    ];

    public function __construct(
        protected int $prg_obj_id,
        protected string $source_type,
        protected int $source_id,
        protected bool $enabled,
        protected int $last_edited_usr_id,
        protected DateTimeImmutable $last_edited,
        protected bool $search_recursive
    ) {
        if (!in_array($source_type, [
            self::TYPE_ROLE,
            self::TYPE_GROUP,
            self::TYPE_COURSE,
            self::TYPE_ORGU
        ])) {
            throw new InvalidArgumentException("Invalid source-type: " . $source_type, 1);
        }
    }

    public function getPrgObjId(): int
    {
        return $this->prg_obj_id;
    }

    public function getSourceType(): string
    {
        return $this->source_type;
    }

    public function getSourceId(): int
    {
        return $this->source_id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getLastEditorId(): int
    {
        return $this->last_edited_usr_id;
    }

    public function getLastEdited(): DateTimeImmutable
    {
        return $this->last_edited;
    }

    public function isSearchRecursive(): bool
    {
        return $this->search_recursive;
    }
}
