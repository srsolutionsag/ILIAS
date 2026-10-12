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

namespace ILIAS;

class ScormAicc implements Component\Component
{
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        // SCORM runtime in a guest frame next to the SCO, see README
        $contribute[Component\Resource\PublicAsset::class] = fn() =>
            new Component\Resource\OfComponent($this, "guest-frame/guest-frame.html", "assets/scorm");
        $contribute[Component\Resource\PublicAsset::class] = fn() =>
            new Component\Resource\ComponentJS($this, "js/guest-frame/dist/scorm-guest-frame-host.js");
        $contribute[Component\Resource\PublicAsset::class] = fn() =>
            new Component\Resource\ComponentJS($this, "js/guest-frame/dist/scorm-guest-frame.js");
        $contribute[Component\Resource\PublicAsset::class] = fn() =>
            new Component\Resource\ComponentJS($this, "js/scorm12-host.js");
        $contribute[Component\Resource\PublicAsset::class] = fn() =>
            new Component\Resource\ComponentJS($this, "js/scorm12-guest.js");

        $contribute[Component\Resource\PublicAsset::class] = static fn() => new class () implements Component\Resource\PublicAsset {
            public function getSource(): string
            {
                return "components/ILIAS/ScormAicc/scripts";
            }
            public function getTarget(): string
            {
                return "components/ILIAS/ScormAicc/scripts";
            }
        };

        $contribute[Component\Resource\PublicAsset::class] = static fn() => new class () implements Component\Resource\PublicAsset {
            public function getSource(): string
            {
                return "components/ILIAS/ScormAicc/templates/default/debug.html";
            }
            public function getTarget(): string
            {
                return "components/ILIAS/ScormAicc/templates/default/debug.html";
            }
        };

        $contribute[Component\Resource\PublicAsset::class] = static fn() => new class () implements Component\Resource\PublicAsset {
            public function getSource(): string
            {
                return "components/ILIAS/ScormAicc/templates/default/dummy.html";
            }
            public function getTarget(): string
            {
                return "components/ILIAS/ScormAicc/templates/default/dummy.html";
            }
        };
    }
}
