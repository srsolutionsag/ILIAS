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

namespace ILIAS\Tests\ScormAicc\GuestFrame;

use ILIAS\ScormAicc\GuestFrame\GuestFrame;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
final class GuestFrameTest extends TestCase
{
    public static function urls(): array
    {
        return [
            'relative' => ['./data/client/lm_data/lm_5/', 'https://ilias.example.org/ilias', './assets/scorm/guest-frame.html'],
            'root relative' => ['/ilias/data/client/lm_data/lm_5/', 'https://ilias.example.org/ilias', './assets/scorm/guest-frame.html'],
            'content domain' => [
                'https://Content.example.org/ilias/deliver.php/abc/-/',
                'https://ilias.example.org/ilias',
                'https://content.example.org/ilias/assets/scorm/guest-frame.html',
            ],
            'port, no path' => [
                'http://localhost:8080/deliver.php/abc/-/',
                'http://localhost:8080',
                'http://localhost:8080/assets/scorm/guest-frame.html',
            ],
        ];
    }

    #[DataProvider('urls')]
    public function testGuestFrameIsOnTheOriginOfThePackage(string $package, string $http_path, string $expected): void
    {
        $this->assertSame($expected, (new GuestFrame($http_path))->getURL($package));
    }

    public static function refused(): array
    {
        return [['javascript:alert(1)'], ['data:text/html,x'], ['ftp://example.org/x'], ['https:///x']];
    }

    #[DataProvider('refused')]
    public function testOtherSchemesAreRefused(string $package): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new GuestFrame('https://ilias.example.org'))->getURL($package);
    }
}
