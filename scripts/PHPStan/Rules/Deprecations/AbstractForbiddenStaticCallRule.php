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

use ILIAS\Scripts\PHPStan\Attributes\RuleViolationAllowance;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reusable base for rules that forbid specific static method calls
 * (`Class::method()`), e.g. deprecated helpers that have moved to a service.
 *
 * A concrete rule only supplies the forbidden `Class::method` map, an identifier
 * and a name — the matching, message building and exemption handling live here.
 *
 * The map is keyed by lower-case class name; the inner map is keyed by lower-case
 * method name (PHP identifiers are case-insensitive) and maps to a short hint that
 * is appended after `Class::method()`. A method key of `'*'` matches every static
 * call on that class.
 *
 * Matching is on the class name as written (fully-qualified or not); `self`/`static`
 * /`parent` calls are not resolved. Exempt with
 * {@see \ILIAS\Scripts\PHPStan\Attributes\AllowRuleViolation} carrying the concrete
 * rule's identifier, or an inline `// @phpstan-ignore <identifier> (reason)` comment.
 */
abstract class AbstractForbiddenStaticCallRule implements Rule
{
    /**
     * @return array<string, array<string, string>> lower-case class => [lower-case method => hint]
     */
    abstract protected function getForbiddenCalls(): array;

    abstract protected function getIdentifier(): string;

    abstract protected function getRuleName(): string;

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    final public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name || !$node->name instanceof Identifier) {
            return [];
        }

        $class = ltrim($node->class->toString(), '\\');
        $method = $node->name->name;

        $map = $this->getForbiddenCalls();
        $class_key = strtolower($class);
        if (!isset($map[$class_key])) {
            return [];
        }

        $methods = $map[$class_key];
        $hint = $methods[strtolower($method)] ?? $methods['*'] ?? null;
        if ($hint === null) {
            return [];
        }

        if (RuleViolationAllowance::isAllowedIn($scope, $this->getIdentifier())) {
            return [];
        }

        return [
            RuleErrorBuilder::message("$class::$method(): $hint")
                ->identifier($this->getIdentifier())
                ->metadata(['rule' => $this->getRuleName(), 'version' => 12])
                ->build(),
        ];
    }
}
