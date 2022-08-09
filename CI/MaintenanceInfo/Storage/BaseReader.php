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

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class BaseReader implements Reader
{
    const MAINTENANCE_JSON = 'maintenance.json';
    protected array $directories = ['Services', 'Modules', 'src'];
    protected Converter $converter;
    private bool $preloaded = false;

    public function __construct()
    {
        $this->converter = new Converter();
    }

    public function demoFile(): InfoFile
    {
        $filename = './CI/MaintenanceInfo/demo.json';

        return $this->converter->stringToInfoFile(
            $filename,
            file_get_contents($filename)
        );
    }


    /**
     * @return \Generator|InfoFile[]
     */
    public function getMissing(): \Generator
    {
        yield from $this->readDirs($this->directories, true);
    }

    /**
     * @return \Generator|InfoFile[]
     */
    public function getExisting(): \Generator
    {
        yield from $this->readDirs($this->directories, false);
    }

    public function preload(): void
    {
        if($this->preloaded) {
            return;
        }
        foreach ($this->getExisting() as $item) {
            // do nothing
        }
        $this->preloaded = true;
    }


    /**
     * @return \Generator|JsonFile[]
     */
    private function readDirs(array $directories, bool $missing_only = false): \Generator
    {
        foreach ($directories as $directory) {
            yield from $this->readDir($directory, $missing_only);
        }
    }

    /**
     * @return \Generator|JsonFile[]
     */
    private function readDir(string $directory, bool $missing_only = false): \Generator
    {
        $d = new \DirectoryIterator($directory);
        while ($d->valid()) {
            $fileInfo = $d->current();
            if ($d->isDot() || $d->isFile()) {
                $d->next();
                continue;
            }
            $filename = $fileInfo->getPathname() . '/' . self::MAINTENANCE_JSON;
            if ($missing_only && !file_exists($filename)) {
                yield $this->converter->stringToInfoFile($filename, '[]');
            } elseif (!$missing_only && file_exists($filename)) {
                yield $this->converter->stringToInfoFile(
                    $filename,
                    file_get_contents($filename)
                );
            }
            $d->next();
        }
    }
}
