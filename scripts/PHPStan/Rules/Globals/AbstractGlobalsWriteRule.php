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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids writing to the `$GLOBALS` array for any key other than `DIC`.
 *
 * `$GLOBALS['ilDB'] = …` is the same legacy container-injection anti-pattern as
 * `global $ilDB` (see {@see NoForeignGlobalRule}), just via the array form.
 * Services belong in the component bootstrap and must be injected. The single
 * tolerated key is `DIC`, as a temporary bridge.
 *
 * Detects `$GLOBALS['x'] = …`, nested `$GLOBALS['x']['y'] = …`, appends
 * `$GLOBALS[] = …`, dynamic keys `$GLOBALS[$x] = …` and whole-array overwrites
 * `$GLOBALS = …`. Concrete subclasses only pick the assignment node type; the
 * detection lives here (all handled nodes expose the target as `$var`).
 *
 * Exempt with {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation}
 * (`'ilias.globalsWrite'`) or its convenience subclass
 * {@see \ILIAS\Scripts\PHPStan\Attributes\AllowGlobalsWrite}; in global scope use an
 * inline `// @phpstan-ignore ilias.globalsWrite (reason)` comment.
 */
abstract class AbstractGlobalsWriteRule implements Rule
{
    public const IDENTIFIER = 'ilias.globalsWrite';

    public const LABEL = '$GLOBALS write';

    /**
     * `$GLOBALS` keys still allowed as a write target.
     *
     * @return list<string>
     */
    protected function getAllowedGlobals(): array
    {
        return ['DIC'];
    }

    final public function processNode(Node $node, Scope $scope): array
    {
        if (!isset($node->var) || !$node->var instanceof Expr) {
            return [];
        }

        $key = $this->globalsWriteKey($node->var);
        if ($key === false) {
            return [];
        }

        if (is_string($key) && in_array($key, $this->getAllowedGlobals(), true)) {
            return [];
        }

        if (RuleViolationAllowance::isAllowedIn($scope, self::IDENTIFIER)) {
            return [];
        }

        $where = is_string($key) ? "\$GLOBALS['$key']" : '$GLOBALS';

        return [
            RuleErrorBuilder::message(
                "Writing to $where is forbidden. Register the service in the component bootstrap "
                . "or inject it instead; only \$GLOBALS['DIC'] is tolerated as a temporary bridge."
            )
                ->identifier(self::IDENTIFIER)
                ->metadata(['rule' => self::LABEL, 'version' => 12])
                ->build(),
        ];
    }

    /**
     * Returns the first-level `$GLOBALS` key written to:
     * - `false`  — not a write to `$GLOBALS`
     * - `null`   — a write to `$GLOBALS` with a non-literal key, an append or a
     *              whole-array overwrite (all forbidden)
     * - string   — the literal key that is written to
     */
    private function globalsWriteKey(Expr $target): false|null|string
    {
        if ($target instanceof Variable) {
            return $target->name === 'GLOBALS' ? null : false;
        }

        $node = $target;
        while ($node instanceof ArrayDimFetch) {
            if ($node->var instanceof Variable && $node->var->name === 'GLOBALS') {
                return $node->dim instanceof String_ ? $node->dim->value : null;
            }
            $node = $node->var;
        }

        return false;
    }
}
