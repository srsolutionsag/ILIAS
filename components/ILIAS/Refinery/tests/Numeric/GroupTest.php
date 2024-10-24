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

namespace ILIAS\Tests\Refinery\Numeric;

use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Numeric\IsNumeric;
use ILIAS\Refinery\Numeric\Group as NumericGroup;
use PHPUnit\Framework\TestCase;
use ILIAS\Language\Language;

class GroupTest extends TestCase
{
    private NumericGroup $group;
    private DataFactory $dataFactory;
    private Language $language;

    protected function setUp(): void
    {
        $this->dataFactory = new DataFactory();
        $this->language = $this->getMockBuilder(Language::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->group = new NumericGroup($this->dataFactory, $this->language);
    }

    public function testIsNumericGroup(): void
    {
        $instance = $this->group->isNumeric();
        $this->assertInstanceOf(IsNumeric::class, $instance);
    }
}
