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

namespace ILIAS\Scripts\PHPStan\Rules\Deprecations;

/**
 * Forbids a curated set of deprecated `ilUtil::` static helpers that have a clear
 * replacement. This is a starter list — extend the map as further ilUtil methods
 * get proper service replacements.
 *
 * Exempt with {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation}
 * (`'ilias.deprecatedIlUtil'`) or an inline
 * `// @phpstan-ignore ilias.deprecatedIlUtil (reason)` comment.
 */
final class NoDeprecatedIlUtilRule extends AbstractForbiddenStaticCallRule
{
    public const IDENTIFIER = 'ilias.deprecatedIlUtil';

    public const LABEL = 'Deprecated ilUtil';

    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getRuleName(): string
    {
        return self::LABEL;
    }

    protected function getForbiddenCalls(): array
    {
        return [
            'ilutil' => [
                'redirect' => 'deprecated, use $DIC->ctrl()->redirectToURL() instead',
                'makeclickable' => 'deprecated, use the Refinery ($refinery->string()->makeClickable()) instead',
                'is_email' => 'deprecated, use ilMailRfc822AddressParserFactory instead',
                'deliverdata' => 'deprecated, use the FileDelivery service instead',
                'getimagepath' => 'deprecated, resolve image paths through the UI service instead',
                'getimagetagbytype' => 'deprecated, render images through the UI service instead',
                'yn2tf' => 'deprecated, use the Refinery instead',
                'tf2yn' => 'deprecated, use the Refinery instead',
                'now' => 'deprecated, use the Clock / Data service instead',
            ],
        ];
    }
}
