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

/**
 * Class OrderingResultExpression for the expression $a,..,n,m$
 *
 * Date: 25.03.13
 * Time: 16:41
 * @author Thomas Joußen <tjoussen@databay.de>
 */
class ilAssLacOrderingResultExpression extends ilAssLacAbstractExpression implements ilAssLacSolutionExpressionInterface
{
    /**
     * The pattern <b>"/\$[0-9]+(?:,[0-9]+)*\$/"</b> should match the following expression in a condition <br />
     * <br />
     * <pre>
     * <b>$a,..,n,m$</b>	all characters are placeholders for numeric indices
     * </pre>
     * It is used to create a OrderingResultExpression
     *
     * @var string
     */
    public static $pattern = '/\$[0-9]+(?:,[0-9]+)*\$/';

    /**
     * @var string
     */
    public static $identifier = '$n,m,o,p$';

    /**
     * An ordered array with numeric indices of elements
     *
     * @var int[]
     */
    protected $ordering;

    protected function getPattern(): string
    {
        return '/(\d+)/';
    }

    /**
     * Sets the result of the parsed value by a specific expression pattern
     * @see ExpressionInterface::parseValue()
     * @see ExpressionInterface::getPattern()
     *
     * @param array $matches
     */
    protected function setMatches($matches): void
    {
        $this->ordering = [];

        foreach ($matches[0] as $match) {
            $this->ordering[] = $match;
        }
    }

    /**
     * @return \int[]
     */
    public function getOrdering(): array
    {
        return $this->ordering;
    }

    /**
     * Get the value of this Expression
     * @return string
     */
    public function getValue(): string
    {
        return "$" . join(",", $this->ordering) . "$";
    }

    /**
     * Get a human readable description of the Composite element
     * @return string
     */
    public function getDescription(): string
    {
        return join(",", $this->ordering) . " beantwortet ";
    }

    /**
     * @param ilUserQuestionResult $result
     * @param string               $comperator
     * @param null|int				$index
     *
     * @return bool
     */
    public function checkResult($result, $comperator, $index = null): bool
    {
        $keys = $result->getUserSolutionsByIdentifier("key");
        $keys = array_filter($keys, function ($element) {
            return $element != null;
        });

        switch ($comperator) {
            case "=":
                return $keys == $this->getOrdering();
                break;
            case "<>":
                return $keys != $this->getOrdering();
                break;
            default:
                return false;
        }
    }
}
