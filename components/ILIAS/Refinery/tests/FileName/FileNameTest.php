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

namespace ILIAS\Refinery\FileNameTest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ILIAS\Refinery\FileName\FileName;

class FileNameTest extends TestCase
{
    private ?FileName $trafo = null;
    protected function setUp(): void
    {
        $this->trafo = new FileName();
    }

    public static function dataProvider(): array
    {
        return [
            ["Control\u{00a0}Character", 'ControlCharacter'],
            ["Soft\u{00ad}Hyphen", 'SoftHyphen'],
            ["No\u{0083}Break", 'NoBreak'],
            ["ZeroWidth\u{200C}NonJoiner", 'ZeroWidthNonJoiner'],
            ["ZeroWidth\u{200d}Joiner", 'ZeroWidthJoiner'],
            ["Invisible\u{2062}Times", 'InvisibleTimes'],
            ["Invisible\u{2063}Comma", 'InvisibleComma'],
            ["Funky\u{200B}Whitespace", 'FunkyWhitespace'],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testTransform(string $original_name, string $expected_name): void
    {
        $this->assertEquals(
            $expected_name,
            $this->trafo->transform($original_name)
        );
    }

}
