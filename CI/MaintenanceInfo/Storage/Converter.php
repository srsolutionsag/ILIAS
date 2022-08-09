<?php

declare(strict_types=1);

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

namespace ILIAS\CI\MaintenanceInfo\Storage;

use DavidBadura\MarkdownBuilder\Alignment;
use DavidBadura\MarkdownBuilder\MarkdownBuilder;
use ILIAS\CI\MaintenanceInfo\Infrastructure\PrimaryPersonResolver;
use ILIAS\CI\MaintenanceInfo\Inventory\Info;
use ILIAS\CI\MaintenanceInfo\Inventory\Person\Maintainer;
use ILIAS\CI\MaintenanceInfo\Inventory\Role\Role;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Converter
{
    private $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    private PrimaryPersonResolver $primary_person;

    public function __construct()
    {
        $this->primary_person = new PrimaryPersonResolver();
    }


    public function stringToInfoFile(string $path, string $string): InfoFile
    {
        return new InfoFile(
            $path,
            json_decode($string, true)
        );
    }

    public function infoFileToString(InfoFile $infoFile): string
    {
        return json_encode($infoFile->getContent(), $this->options);
    }

    public function infoFileToMarkDown(InfoFile $infoFile, Info $info): string
    {
        $builder = new MarkdownBuilder();
        $builder->h1(dirname($infoFile->getPath()));
        $builder->h2('General Information');
        $component = $info->getComponent();
        $model = 'Unmaintained';
        $persons = [['Primary Contact', $this->primary_person->resolve($info)->getDocuUserName()]];
        foreach ($component->getRoles()->get() as $role) {
            /** @var $role Role */
            if ($role->getName() === Role::ROLE_COORDINATOR) {
                $model = 'Coordinator';
            }
            if ($role->getName() === Role::ROLE_FIRST_MAINTAINER) {
                $model = 'Maintainer';
            }
            $persons[] = [$role->getName(), $role->getPerson()->getDocuUserName() ?? 'Unknown'];
        }
        $builder->p('Belongs to Component: ' . ($component->getTitle() ?? 'Unknown'));
        $builder->p('Model: ' . $model);
        $builder->h2('Persons');


        $builder->table(
            ['Role', 'Person'],
            $persons,
            [Alignment::LEFT, Alignment::LEFT]
        );

        return $builder->getMarkdown();
    }

    public function infoToMarkdown(Info $info):string
    {
        $builder = new MarkdownBuilder();

        return $builder->getMarkdown();

    }
}
