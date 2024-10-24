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
* Unit tests
*
* @author Maximilian Becker <mbecker@databay.de>
*
* @ingroup components\ILIASTestQuestionPool
*/
class assAnswerBinaryStateImageTest extends assBaseTestCase
{
    protected $backupGlobals = false;

    protected function setUp(): void
    {
        chdir(__DIR__ . '/../../../../');
    }

    public function test_instantiateObject_shouldReturnInstance(): void
    {
        $instance = new ASS_AnswerBinaryStateImage();

        $this->assertInstanceOf(ASS_AnswerBinaryStateImage::class, $instance);
    }

    public function test_setGetImage(): void
    {
        $instance = new ASS_AnswerBinaryStateImage();
        $expected = 'image';
        $instance->setImage($expected);
        $actual = $instance->getImage();

        $this->assertEquals($expected, $actual);
    }
}
