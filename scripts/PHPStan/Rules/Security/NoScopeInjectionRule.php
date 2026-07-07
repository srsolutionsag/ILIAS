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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids injecting variables into scope from arrays / request data.
 *
 * Flags:
 * - `extract()` — always; it pulls arbitrary array keys into local variables.
 * - `parse_str()` — only the dangerous forms: without a result array (writes into
 *   the local scope) or with a superglobal as the result array (mutates the
 *   request). `parse_str($str, $ownArray)` into a plain local array is allowed.
 *
 * Exempt with {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation}
 * (`'ilias.scopeInjection'`) or an inline
 * `// @phpstan-ignore ilias.scopeInjection (reason)` comment.
 *
 * @implements Rule<FuncCall>
 */
final class NoScopeInjectionRule implements Rule
{
    public const IDENTIFIER = 'ilias.scopeInjection';

    public const LABEL = 'Scope injection';

    private const SUPERGLOBALS = ['_GET', '_POST', '_REQUEST', '_COOKIE', '_FILES', '_SESSION', '_SERVER', '_ENV'];

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
        $message = match ($function) {
            'extract' => 'extract() injects variables from an array into the local scope and is forbidden. '
                . 'Access the array values explicitly instead.',
            'parse_str' => $this->parseStrMessage($node),
            default => null,
        };

        if ($message === null) {
            return [];
        }

        if (RuleViolationAllowance::isAllowedIn($scope, self::IDENTIFIER)) {
            return [];
        }

        return [
            RuleErrorBuilder::message($message)
                ->identifier(self::IDENTIFIER)
                ->metadata(['rule' => self::LABEL, 'version' => 12])
                ->build(),
        ];
    }

    private function parseStrMessage(FuncCall $node): ?string
    {
        $args = $node->getArgs();

        if (count($args) < 2) {
            return 'parse_str() without a result array writes variables into the local scope and is forbidden. '
                . 'Pass an explicit result array as the second argument.';
        }

        $superglobal = $this->rootSuperglobal($args[1]->value);
        if ($superglobal !== null) {
            return "parse_str() must not write into the superglobal \$$superglobal. "
                . 'The request is immutable; parse into an explicit local array instead.';
        }

        return null;
    }

    private function rootSuperglobal(Expr $expr): ?string
    {
        while ($expr instanceof ArrayDimFetch) {
            $expr = $expr->var;
        }

        if ($expr instanceof Variable
            && is_string($expr->name)
            && in_array($expr->name, self::SUPERGLOBALS, true)
        ) {
            return $expr->name;
        }

        return null;
    }
}
