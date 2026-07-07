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

namespace ILIAS\Scripts\PHPStan\Rules\Security;

use ILIAS\Scripts\PHPStan\Attributes\RuleViolationAllowance;
use PhpParser\Node;
use PhpParser\Node\Expr\Eval_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids the `eval()` language construct: it executes arbitrary PHP and is a
 * critical code-injection risk.
 *
 * Exempt with {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation}
 * (`'ilias.eval'`) on the enclosing declaration, or an inline
 * `// @phpstan-ignore ilias.eval (reason)` comment.
 *
 * @implements Rule<Eval_>
 */
final class NoEvalRule implements Rule
{
    public const IDENTIFIER = 'ilias.eval';

    public const LABEL = 'eval()';

    public function getNodeType(): string
    {
        return Eval_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (RuleViolationAllowance::isAllowedIn($scope, self::IDENTIFIER)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Use of eval() is forbidden: it executes arbitrary code and is a critical security risk.'
            )
                ->identifier(self::IDENTIFIER)
                ->metadata(['rule' => self::LABEL, 'version' => 12])
                ->build(),
        ];
    }
}
