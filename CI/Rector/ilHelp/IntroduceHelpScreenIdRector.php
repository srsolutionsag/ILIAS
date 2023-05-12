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
use ILIAS\Services\Help\ScreenId\ClassNameToScreenId;
use ILIAS\Services\Help\ScreenId\HelpScreenId;

final class IntroduceHelpScreenIdRector extends \Rector\Core\Rector\AbstractRector
{
    use ClassNameToScreenId;

    private array $ilctrl_classes = [];

    public function __construct()
    {
        $this->ilctrl_classes = array_map(function (array $class_info) {
            return $class_info['class_name'];
        }, include __DIR__ . '/../../../Services/UICore/artifacts/ctrl_structure.php');
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    private function isApplicable(Node $node): bool
    {
        /** @var $node Node\Stmt\Class_ */
        if (!$node->name instanceof \PhpParser\Node\Identifier) {
            // node has no name
            return false;
        }
        // not interested in method since not in list
        return in_array($node->name->name, $this->ilctrl_classes);
    }

    /**
     * @param Node $node the Static Call to ilUtil:sendXY
     */
    public function refactor(Node $node)
    {
        if (!$this->isApplicable($node)) {
            return null; // leave the node as it is
        }
        // check for existing attribute
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === HelpScreenId::class) {
                    return null; // leave the node as it is
                }
            }
        }

        $node->attrGroups = array_merge([
            new Node\AttributeGroup([
                new Node\Attribute(new Node\Name(HelpScreenId::class), [
                    new Node\Arg(new Node\Scalar\String_($this->classNameToScreenId($node->name->name)))
                ])
            ])
        ], $node->attrGroups);

        return $node;
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
