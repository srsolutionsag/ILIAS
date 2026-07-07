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

namespace ILIAS\Scripts\PHPStan\Rules\Environment;

use ILIAS\Scripts\PHPStan\Attributes\RuleViolationAllowance;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids process-global runtime mutation via `ini_set()`, `putenv()`,
 * `setlocale()` and `date_default_timezone_set()`.
 *
 * These change global interpreter state for the whole request and leak across
 * unrelated code. Configuration belongs in the ILIAS bootstrap / configuration,
 * locale and timezone into the respective ILIAS services.
 *
 * Exempt with {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation}
 * (`'ilias.environmentMutation'`) or an inline
 * `// @phpstan-ignore ilias.environmentMutation (reason)` comment.
 *
 * @implements Rule<FuncCall>
 */
final class NoEnvironmentMutationRule implements Rule
{
    public const IDENTIFIER = 'ilias.environmentMutation';

    public const LABEL = 'Environment mutation';

    private const FORBIDDEN = [
        'ini_set',
        'putenv',
        'setlocale',
        'date_default_timezone_set',
    ];

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $function = strtolower($node->name->toString());
        if (!in_array($function, self::FORBIDDEN, true)) {
            return [];
        }

        if (RuleViolationAllowance::isAllowedIn($scope, self::IDENTIFIER)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                "Global runtime mutation via $function() is forbidden. Configure the environment "
                . 'through the ILIAS bootstrap / configuration instead.'
            )
                ->identifier(self::IDENTIFIER)
                ->metadata(['rule' => self::LABEL, 'version' => 12])
                ->build(),
        ];
    }
}
