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

use PHPUnit\Framework\TestCase;

class ilQTIMatImageSecurityTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->assertInstanceOf(
            ilQtiMatImageSecurity::class,
            new ilQtiMatImageSecurity(
                $this->image(),
                $this->createMock(\ILIAS\TestQuestionPool\Questions\Files\QuestionFiles::class)
            )
        );
    }

    private function image(): ilQTIMatimage
    {
        $image = $this->getMockBuilder(ilQTIMatimage::class)->disableOriginalConstructor()->getMock();
        $image->expects(self::exactly(2))->method('getRawContent')->willReturn('Ayayay');

        return $image;
    }
}
