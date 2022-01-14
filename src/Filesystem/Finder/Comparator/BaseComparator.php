<?php
declare(strict_types=1);

namespace ILIAS\Filesystem\Finder\Comparator;

use InvalidArgumentException;

/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
 * Class Base
 * @package ILIAS\Filesystem\Finder\Comparator
 * @author  Michael Jansen <mjansen@databay.de>
 */
abstract class BaseComparator
{
    /** @var string */
    private $target = '';
    /** @var string */
    private $operator = '==';

    /**
     * @return string
     */
    public function getTarget() : string
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getOperator() : string
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     * @throws InvalidArgumentException
     */
    public function setOperator(string $operator)
    {
        if (0 === strlen($operator)) {
            $operator = '==';
        }

        if (!in_array($operator, ['>', '<', '>=', '<=', '==', '!='])) {
            throw new InvalidArgumentException(sprintf('Invalid operator "%s".', $operator));
        }

        $this->operator = $operator;
    }

    /**
     * @param string $test
     * @return bool
     */
    public function test(string $test) : bool
    {
        switch ($this->operator) {
            case '>':
                return $test > $this->target;

            case '>=':
                return $test >= $this->target;

            case '<':
                return $test < $this->target;

            case '<=':
                return $test <= $this->target;

            case '!=':
                return $test != $this->target;
        }

        return $test == $this->target;
    }
}
