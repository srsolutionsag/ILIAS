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

namespace ILIAS\Scripts\PHPStan\Rules\Session;

use ILIAS\Scripts\PHPStan\Attributes\RuleViolationAllowance;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids direct access to the `$_SESSION` superglobal (reading or writing).
 *
 * Session state must go through the `ilSession` wrapper (`ilSession::get()` /
 * `ilSession::set()`), which owns key namespacing and lifecycle. Touching
 * `$_SESSION` directly bypasses it.
 *
 * Exempt with {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation}
 * (`'ilias.sessionAccess'`) — e.g. on `ilSession` itself — or an inline
 * `// @phpstan-ignore ilias.sessionAccess (reason)` comment.
 *
 * @implements Rule<Variable>
 */
final class NoSessionAccessRule implements Rule
{
    public const IDENTIFIER = 'ilias.sessionAccess';

    public const LABEL = 'Direct $_SESSION access';

    public function getNodeType(): string
    {
        return Variable::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name !== '_SESSION') {
            return [];
        }

        if (RuleViolationAllowance::isAllowedIn($scope, self::IDENTIFIER)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Direct access to $_SESSION is forbidden. Use the ilSession wrapper '
                . '(ilSession::get() / ilSession::set()) instead.'
            )
                ->identifier(self::IDENTIFIER)
                ->metadata(['rule' => self::LABEL, 'version' => 12])
                ->build(),
        ];
    }
}
