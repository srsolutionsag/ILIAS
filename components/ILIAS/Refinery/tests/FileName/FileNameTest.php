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

namespace ILIAS\Refinery\FileNameTest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ILIAS\Refinery\FileName\FileName;

class FileNameTest extends TestCase
{
    private ?FileName $trafo = null;

    protected function setUp(): void
    {
        $this->trafo = new FileName();
    }

    public static function dataProvider(): array
    {
        return [
            ["Control\u{00a0}Character", 'ControlCharacter'],
            ["Soft\u{00ad}Hyphen", 'SoftHyphen'],
            ["No\u{0083}Break", 'NoBreak'],
            ["ZeroWidth\u{200C}NonJoiner", 'ZeroWidthNonJoiner'],
            ["ZeroWidth\u{200d}Joiner", 'ZeroWidthJoiner'],
            ["Invisible\u{2062}Times", 'InvisibleTimes'],
            ["Invisible\u{2063}Comma", 'InvisibleComma'],
            ["Funky\u{200B}Whitespace", 'FunkyWhitespace'],
            ["ZeroWidth\u{2000}Ogham", 'ZeroWidth Ogham'],
            ["ZeroWidth\u{2001}HairSpace", 'ZeroWidth HairSpace'],
            ["ZeroWidth\u{2002}EnQuad", 'ZeroWidth EnQuad'],
            ["ZeroWidth\u{2003}EmQuad", 'ZeroWidth EmQuad'],
            ["ZeroWidth\u{2004}ThreePerEm", 'ZeroWidth ThreePerEm'],
            ["ZeroWidth\u{2005}FourPerEm", 'ZeroWidth FourPerEm'],
            ["ZeroWidth\u{2006}FivePerEm", 'ZeroWidth FivePerEm'],
            ["ZeroWidth\u{2007}SixPerEm", 'ZeroWidth SixPerEm'],
            ["ZeroWidth\u{2008}ModeSwitch", 'ZeroWidth ModeSwitch'],
            ["ZeroWidth\u{2009}Thinspace", 'ZeroWidth Thinspace'],
            ["ZeroWidth\u{200A}Hairspace", 'ZeroWidth Hairspace'],
            ["ZeroWidth\u{200B}FunkyWhitespace", 'ZeroWidth FunkyWhitespace'],
            ["example/file.txt", "example_file.txt"],
            ["test\\file.txt", "test_file.txt"],
            ["hello:world.txt", "hello_world.txt"],
            ["my*file?.txt", "my_file_.txt"],
            ["quote\"marks\".txt", "quote_marks_.txt"],
            ["pipe|symbol.txt", "pipe_symbol.txt"],
            ["greater>lesser.txt", "greater_lesser.txt"],
            ["colon:file.txt", "colon_file.txt"],
            ["slash/file/name.txt", "slash_file_name.txt"],
            ["back\\slash\\name.txt", "back_slash_name.txt"],
            ["question?mark.txt", "question_mark.txt"],
            ["asterisk*file.txt", "asterisk_file.txt"],
            ["angle<brackets>.txt", "angle_brackets_.txt"],
            ["pipe|in|name.txt", "pipe_in_name.txt"],
            ["new\nline.txt", "new_line.txt"],
            ["tab\tcharacter.txt", "tab_character.txt"],
            ["file_with_$dollar.txt", "file_with__dollar.txt"],
            ["caret^symbol.txt", "caret_symbol.txt"],
            ["percent%sign.txt", "percent_sign.txt"],
            ["ampersand&symbol.txt", "ampersand_symbol.txt"],
            ["hash#symbol.txt", "hash_symbol.txt"],
            ["at@symbol.txt", "at_symbol.txt"],
            ["braces{file}.txt", "braces_file_.txt"],
            ["brackets[file].txt", "brackets_file_.txt"],
            ["parentheses(file).txt", "parentheses_file_.txt"],
            ["exclamation!mark.txt", "exclamation_mark.txt"],
            ["tilde~symbol.txt", "tilde_symbol.txt"],
            ["plus+sign.txt", "plus_sign.txt"],
            ["equals=sign.txt", "equals_sign.txt"],
            ["colon:in:name.txt", "colon_in_name.txt"],
            ["double\"quotes.txt", "double_quotes.txt"],
            ["single'quote.txt", "single_quote.txt"],
            ["semi;colon.txt", "semi_colon.txt"],
            ["comma,name.txt", "comma_name.txt"],
            ["space in name.txt", "space_in_name.txt"],
            // Spaces replaced with underscores
            ["under_score.txt", "under_score.txt"],
            // Underscore is allowed
            ["dot.file.txt", "dot.file.txt"],
            // Dot is allowed
            ["hyphen-name.txt", "hyphen-name.txt"],
            // Hyphen is allowed
            ["mixed_chars!@#$%^&*()[]{}<>?|`~.txt", "mixed_chars_.txt"],
            ["whitespace\nin\tname.txt", "whitespace_in_name.txt"],
            ["emoji😊name.txt", "emoji_name.txt"],
            ["café.txt", "cafe.txt"],
            // Unicode character é removed
            ["ümläut.txt", "umlaut.txt"],
            // Unicode character ü removed
            ["cyrillic_файл.txt", "cyrillic_file.txt"],
            // Cyrillic characters removed
            ["chinese_文件.txt", "chinese_file.txt"],
            // Chinese characters removed
            ["arabic_ملف.txt", "arabic_file.txt"],
            // Arabic characters removed
            ["hebrew_קובץ.txt", "hebrew_file.txt"],
            // Hebrew characters removed
            [
                "long-file-name-with-many-characters-1234567890.txt",
                "long-file-name-with-many-characters-1234567890.txt"
            ],
            ["mixed_αβγ.txt", "mixed_.txt"],
            // Greek letters removed
            ["dollar$ign.txt", "dollar_ign.txt"],
            ["caret^top.txt", "caret_top.txt"],
            ["excess!!chars!!txt", "excess_chars_txt"],
            ["weird😀emoji😜chars.txt", "weird_chars.txt"],
            // Emojis removed
            ["turkçe_karakterler_şçöüğ.txt", "turkce_karakterler_scoeg.txt"],
            // Turkish characters replaced with plain Latin equivalents
            ["smart_quotes_“”‘’.txt", "smart_quotes_.txt"],
            ["ogham_᚛᚜.txt", "ogham_.txt"],
            // Ogham characters removed
            ["math_symbols_∑√π.txt", "math_symbols_.txt"],
            // Math symbols removed
            ["currency_symbols_¥€£₹.txt", "currency_symbols_.txt"],
            // Currency symbols removed
            ["accéntèd_chars.txt", "accented_chars.txt"],
            // Accented characters removed
            ["chess_symbols_♔♕♖♗♘♙.txt", "chess_symbols_.txt"],
            // Chess symbols removed
            ["currency$€£.txt", "currency_.txt"],
            ["line|separated|name.txt", "line_separated_name.txt"],
            ["percentage%file.txt", "percentage_file.txt"],
            ["tilde~file.txt", "tilde_file.txt"],
            ["caret^sign.txt", "caret_sign.txt"],
            ["brackets[]file.txt", "brackets_file.txt"],
            ["accented_ñame.txt", "accented_name.txt"],
            // Accented character ñ removed
            ["composite_ﬁle.txt", "composite_file.txt"],
            // Ligature fi replaced
            ["curly_quotes_‘’.txt", "curly_quotes_.txt"],
            ["bullet•points.txt", "bullet_points.txt"],
            // Bullet point removed
            ["line_break_\n_file.txt", "line_break__file.txt"],
            // Line break replaced
            ["vertical|bar.txt", "vertical_bar.txt"],
            ["equal=signs.txt", "equal_signs.txt"],
            ["section§symbol.txt", "section_symbol.txt"],
            ["plus+signs.txt", "plus_signs.txt"],
            ["percentage%%file.txt", "percentage_file.txt"],
            ["weird|pipe|file.txt", "weird_pipe_file.txt"],
            ["forward/slash/name.txt", "forward_slash_name.txt"],
            ["back\\slash\\file.txt", "back_slash_file.txt"],
            ["colon:name:file.txt", "colon_name_file.txt"],
            ["semi;colon;file.txt", "semi_colon_file.txt"],
            ["backtick`file.txt", "backtick_file.txt"],
            ["double--dash.txt", "double_dash.txt"],
            ["long—dash.txt", "long_dash.txt"],
            // Long dash replaced with short dash
            ["left<angle>right.txt", "left_angle_right.txt"],
            ["curly{braces}.txt", "curly_braces_.txt"],
            ["square[brackets].txt", "square_brackets_.txt"],
            ["quote\"double\".txt", "quote_double_.txt"],
            ["single'quote'.txt", "single_quote_.txt"],
            ["fancy—dash.txt", "fancy_dash.txt"],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testTransform(string $original_name, string $expected_name): void
    {
        $this->assertEquals(
            $expected_name,
            $this->trafo->transform($original_name)
        );
    }
}
