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
    private const LINE_SEPARATOR = '/[\x0A\x0D\x85\u2028\u2029]/u';
    private const CONTROL_CHARACTER = "/\\x{00a0}/iu";

    public function transform($filename)
    {
        if (!is_string($filename)) {
            throw new InvalidArgumentException(__METHOD__ . " the argument is not a string.");
        }
        $filename = trim($filename, "_ ");
        // /[\/\\\:*?"<>|]|[\x00-\x1F\x7F]|[^.\p{P}][^.|\p{P}]|\p{S}|\p{C}|\p{M}|\p{Z}|\P{Latin}/u

        $filename = preg_replace(
            '/[\/\\\:*?"<>|]|[\x00-\x1F\x7F][^.|\p{P}]|\p{S}|\p{C}|\p{M}|\p{Z}|\P{Latin}/u',
            '_',
            $filename
        );

        return preg_replace('/\_+/', '_', $filename);

        $filename = preg_replace('/[^A-Za-z0-9._\- ]/', '_', $filename);
        // replace multiple spaces with a single space
        $filename = preg_replace('/_+/', '_', $filename);

        return trim((string) $filename, "_ ");

        // remove quotes
        $filename = preg_replace('/["\']/', '', $filename);

        // remove control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);
        $filename = preg_replace(self::CONTROL_CHARACTER, '', $filename);

        // remove other characters
        $filename = preg_replace(self::FUNKY_WHITESPACES, '', $filename);
        $filename = preg_replace(self::SOFT_HYPHEN, ' ', $filename);
        $filename = preg_replace(self::ZERO_JOINER, '', $filename);

        // Remove control characters, except for newline (0x0A) and tab (0x09)
        $filename = preg_replace('/[^\x20-\xFF\x0A\x09]/', '', $filename);

        $chrs = [
            chr(0x2000),
            chr(0x2001),
            chr(0x2002),
            chr(0x2003),
            chr(0x2004),
            chr(0x2005),
            chr(0x2006),
            chr(0x2007),
            chr(0x2008),
            chr(0x2009),
            chr(0x200A),
            chr(0x200B),
            chr(0x2028),
            chr(0x2029),
            chr(10),
            chr(13),
            chr(32),
            chr(8212),
            chr(0xA0),
            chr(0x20),
            chr(0x96),
            chr(0xA),
            chr(0xD),

        ];

        // replace line separators and special whitespaces
        $filename = str_replace($chrs, " ", $filename);

        // Replace funky whitespace characters with standard spaces
        $filename = preg_replace('/[\p{Zs}\t]+/', '', $filename);

        // Remove leading and trailing whitespace
        $filename = trim((string) $filename);

        // Replace special characters
        $filename = preg_replace('/[*%?\/\\\^\.<>|~`$\§#@\+\=\[\]\(\)\{\}]/', '-', $filename);

        // UTF normalization form C
        return (new UTFNormal())->formC()->transform($filename);
    }

}
