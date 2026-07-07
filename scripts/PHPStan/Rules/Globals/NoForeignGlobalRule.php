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

namespace ILIAS\Scripts\PHPStan\Rules\Globals;

use ILIAS\Scripts\PHPStan\Attributes\RuleViolationAllowance;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Global_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids the `global` statement for any variable other than `$DIC`.
 *
 * Reaching into global scope couples code to the runtime environment and hides its
 * real dependencies. Dependencies must be injected. The single tolerated exception
 * is `global $DIC` — and even that is only a temporary bridge until the component is
 * migrated to the bootstrap mechanism.
 *
 * `global $DIC, $ilDB;` reports only `$ilDB`; each disallowed variable of a single
 * `global` statement is reported individually.
 *
 * A class, method or function may be exempted with the
 * {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation} attribute (or its
 * convenience subclass {@see \ILIAS\Scripts\PHPStan\Attributes\AllowForeignGlobal}).
 * A `global` statement in global scope (e.g. a resource script) has no enclosing
 * declaration to annotate; use an inline
 * `// @phpstan-ignore ilias.foreignGlobal (reason)` comment there.
 *
 * @implements Rule<Global_>
 */
final class NoForeignGlobalRule implements Rule
{
    public const IDENTIFIER = 'ilias.foreignGlobal';

    public const LABEL = 'Non-DIC global';

    /**
     * Variable names (without leading `$`) still allowed in a `global` statement.
     *
     * @return list<string>
     */
    protected function getAllowedGlobals(): array
    {
        return ['DIC'];
    }

    public function getNodeType(): string
    {
        return Global_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $allowed = $this->getAllowedGlobals();

        $forbidden = [];
        foreach ($node->vars as $var) {
            if ($var instanceof Variable
                && is_string($var->name)
                && !in_array($var->name, $allowed, true)
            ) {
                $forbidden[] = $var->name;
            }
        }

        if ($forbidden === []) {
            return [];
        }

        if (RuleViolationAllowance::isAllowedIn($scope, self::IDENTIFIER)) {
            return [];
        }

        return array_map(
            static fn(string $name) => RuleErrorBuilder::message(
                "Use of `global \$$name` is forbidden. Inject the dependency instead; "
                . 'only `global $DIC` is tolerated as a temporary bridge.'
            )
                ->identifier(self::IDENTIFIER)
                ->metadata([
                    'rule' => self::LABEL,
                    'version' => 12,
                ])
                ->build(),
            $forbidden
        );
    }
}
