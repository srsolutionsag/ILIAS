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

use PHPUnit\Framework\TestCase;
use ILIAS\TestQuestionPool\Questions\SuggestedSolution\SuggestedSolutionsDatabaseRepository;
use ILIAS\TestQuestionPool\Questions\SuggestedSolution\SuggestedSolution;
use ILIAS\TestQuestionPool\Questions\SuggestedSolution\SuggestedSolutionText;
use ILIAS\TestQuestionPool\Questions\SuggestedSolution\SuggestedSolutionFile;
use ILIAS\TestQuestionPool\Questions\SuggestedSolution\SuggestedSolutionLink;

/**
 * test the suggested solution immutable(s)
 *
 * @author Nils Haagen <nils.haagen@concepts-and-training.de>
*/
class SuggestedSolutionRepoMock extends SuggestedSolutionsDatabaseRepository
{
    public function getSolution(
        int $id,
        int $question_id,
        string $internal_link,
        string $import_id,
        int $subquestion_index,
        string $type,
        string $value,
        DateTimeImmutable $last_update
    ): SuggestedSolution {
        return $this->buildSuggestedSolution(
            $id,
            $question_id,
            $internal_link,
            $import_id,
            $subquestion_index,
            $type,
            $value,
            $last_update
        );
    }
}

class SuggestedSolutionTest extends TestCase
{
    private SuggestedSolutionRepoMock $repo;
    protected function setUp(): void
    {
        $this->repo = new SuggestedSolutionRepoMock(
            $this->createMock(ilDBInterface::class),
        );
    }

    public function testSuggestedSolutionFile(): SuggestedSolutionFile
    {
        $id = 123;
        $question_id = 321;
        $internal_link = '';
        $import_id = 'imported_xy';
        $subquestion_index = 0;
        $type = SuggestedSolution::TYPE_FILE;

        $values = [
            'name' => 'something.jpg',
            'type' => 'image/jpeg',
            'size' => 120,
            'filename' => 'actually title of file',
        ];

        $last_update = new DateTimeImmutable();

        $sugsol = $this->repo->getSolution(
            $id,
            $question_id,
            $internal_link,
            $import_id,
            $subquestion_index,
            $type,
            serialize($values),
            $last_update,
        );
        $this->assertInstanceOf(SuggestedSolution::class, $sugsol);
        $this->assertInstanceOf(SuggestedSolutionFile::class, $sugsol);

        $this->assertEquals($values[$sugsol::ARRAY_KEY_TITLE], $sugsol->getTitle());
        $this->assertEquals($values[$sugsol::ARRAY_KEY_MIME], $sugsol->getMime());
        $this->assertEquals($values[$sugsol::ARRAY_KEY_SIZE], $sugsol->getSize());
        $this->assertEquals($values[$sugsol::ARRAY_KEY_FILENAME], $sugsol->getFilename());
        $this->assertEquals(serialize($values), $sugsol->getStorableValue());
        $this->assertTrue($sugsol->isOfTypeFile());
        $this->assertFalse($sugsol->isOfTypeLink());

        return $sugsol;
    }


    /**
     * @depends testSuggestedSolutionFile
     */
    public function testSuggestedSolutionMutatorsFile(SuggestedSolutionFile $sugsol): void
    {
        $values = [
            'name' => 'somethingelse.ico',
            'type' => 'image/x-icon',
            'size' => 11,
            'filename' => '',
        ];

        $sugsol = $sugsol
            ->withTitle($values['filename'])
            ->withMime($values['type'])
            ->withSize($values['size'])
            ->withFilename($values['name']);

        $this->assertEquals($values['name'], $sugsol->getTitle());
        $this->assertEquals($values['name'], $sugsol->getFileName());
        $this->assertEquals($values['type'], $sugsol->getMime());
        $this->assertEquals($values['size'], $sugsol->getSize());

        $nu_title = 'another title';
        $this->assertEquals($nu_title, $sugsol->withTitle($nu_title)->getTitle());
    }
}
