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

namespace ILIAS\Refinery\FileName;

use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\DeriveApplyToFromTransform;
use ILIAS\Refinery\DeriveInvokeFromTransform;
use ILIAS\Refinery\String\UTFNormal;

class FileName implements Transformation
{
    use DeriveApplyToFromTransform;
    use DeriveInvokeFromTransform;
    private const FUNKY_WHITESPACES = '#\p{C}+#u';
    private const ZERO_JOINER = '/\\x{00ad}|\\x{0083}|\\x{200c}|\\x{200d}|\\x{2062}|\\x{2063}/iu';
    private const SOFT_HYPHEN = "/\\x{00a0}/iu";
    private const CONTROL_CHARACTER = "/\\x{00a0}/iu";

    public function transform($from)
    {
        if (!is_string($from)) {
            throw new InvalidArgumentException(__METHOD__ . " the argument is not a string.");
        }

        // remove control characters
        $from = preg_replace('/[\x00-\x1F\x7F]/u', '', $from);
        $from = preg_replace(self::CONTROL_CHARACTER, '', $from);

        // remove other characters
        $from = preg_replace(self::FUNKY_WHITESPACES, '', $from);
        $from = preg_replace(self::SOFT_HYPHEN, ' ', $from);
        $from = preg_replace(self::ZERO_JOINER, '', $from);

        // UTF normalization form C
        return (new UTFNormal())->formC()->transform($from);
    }

}
