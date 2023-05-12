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

namespace ILIAS\CI\Rector\ilHelp;

use PhpParser\Node;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Rector\Php80\NodeFactory\AttrGroupsFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Php80\NodeManipulator\AttributeGroupNamedArgumentManipulator;
use Rector\Naming\Naming\UseImportsResolver;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use ILIAS\Services\Help\ScreenId\HelpScreenId;

final class ReplaceHelpMethodsRector extends \Rector\Core\Rector\AbstractRector
{
    protected array $old_method_names = [
        'setScreenIdComponent',
        'setScreenId',
    ];

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    private function isApplicable(Node $node): bool
    {
        /** @var $node \PhpParser\Node\Expr\MethodCall::class */
        if (!$node->name instanceof \PhpParser\Node\Identifier) {
            // node has no name
            return false;
        }
        // not interested in method since not in list
        return in_array($node->name->name, $this->old_method_names);
    }

    public function refactor(Node $node)
    {
        if (!$this->isApplicable($node)) {
            return null; // leave the node as it is
        }
        $value = $node->getArgs()[0]->value;

        $parent_class = $this->betterNodeFinder->findParentType(
            $node,
            \PhpParser\Node\Stmt\Class_::class
        );

        // check for existing attribute
        foreach ($parent_class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === HelpScreenId::class) {
                    return null; // leave the node as it is
                }
            }
        }

        $parent_class->attrGroups = array_merge([
            new Node\AttributeGroup([
                new Node\Attribute(new Node\Name(HelpScreenId::class), [
                    new Node\Arg($value)
                ])
            ])
        ], $parent_class->attrGroups);

        $this->removeNode($node);

        return null;
    }

    public function getRuleDefinition(): \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new RuleDefinition("", [
            new CodeSample(
                "",
                ""
            )
        ]);
    }
}
