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

use ILIAS\Filesystem\Filesystem;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Location;
use ILIAS\Filesystem\Exception\IOException;

/**
 * Class ilObjectCustomIconImpl
 * TODO: Inject database persistence in future instead of using \ilContainer
 */
class ilObjectCustomIcon
{
    private const ICON_BASENAME = 'icon_custom';

    public function __construct(
        protected Filesystem $filesystem,
        protected FileUpload $upload,
        protected ilObjectCustomIconConfiguration $config,
        protected int $objId
    ) {
    }

    protected function getObjId(): int
    {
        return $this->objId;
    }

    public function copy(int $targetObjId): void
    {
        if (!$this->exists()) {
            ilContainer::_writeContainerSetting($targetObjId, 'icon_custom', '0');
            return;
        }

        try {
            $this->filesystem->copy(
                $this->getRelativePath(),
                preg_replace(
                    '/(' . $this->config->getSubDirectoryPrefix() . ')(\d*)\/(.*)$/',
                    '${1}' . $targetObjId . '/${3}',
                    $this->getRelativePath()
                )
            );

            ilContainer::_writeContainerSetting($targetObjId, 'icon_custom', '1');
        } catch (Exception $e) {
            ilContainer::_writeContainerSetting($targetObjId, 'icon_custom', '0');
        }
    }

    public function delete(): void
    {
        if ($this->filesystem->hasDir($this->getIconDirectory())) {
            try {
                $this->filesystem->deleteDir($this->getIconDirectory());
            } catch (Exception $e) {
            }
        }

        ilContainer::_deleteContainerSettings($this->getObjId(), 'icon_custom');
    }

    /**
     * @return string[]
     */
    public function getSupportedFileExtensions(): array
    {
        return $this->config->getSupportedFileExtensions();
    }

    public function saveFromSourceFile(string $sourceFilePath): void
    {
        $this->createCustomIconDirectory();

        $fileName = $this->getRelativePath();

        if ($this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }

        $this->filesystem->copy($sourceFilePath, $fileName);

        $this->persistIconState($fileName);
    }

    public function saveFromTempFileName(string $tempfile_name): void
    {
        $this->createCustomIconDirectory();

        $relative_path = $this->getRelativePath();

        if ($this->filesystem->has($relative_path)) {
            $this->filesystem->delete($relative_path);
        }

        rename(ilFileUtils::getDataDir() . '/temp/' . $tempfile_name, $this->getFullPath());


        foreach ($this->config->getUploadPostProcessors() as $processor) {
            $processor->process($relative_path);
        }

        $this->persistIconState($relative_path);
    }

    public function saveFromHttpRequest(): void
    {
        $this->createCustomIconDirectory();

        $fileName = $this->getRelativePath();

        if ($this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }

        if ($this->upload->hasUploads() && !$this->upload->hasBeenProcessed()) {
            $this->upload->process();

            /** @var UploadResult $result */
            $result = array_values($this->upload->getResults())[0];
            if ($result->isOK()) {
                $this->upload->moveOneFileTo(
                    $result,
                    $this->getIconDirectory(),
                    Location::WEB,
                    $this->getIconFileName(),
                    true
                );
            }

            foreach ($this->config->getUploadPostProcessors() as $processor) {
                $processor->process($fileName);
            }
        }

        $this->persistIconState($fileName);
    }

    protected function persistIconState(string $fileName): void
    {
        if ($this->filesystem->has($fileName)) {
            ilContainer::_writeContainerSetting($this->getObjId(), 'icon_custom', '1');
        } else {
            ilContainer::_writeContainerSetting($this->getObjId(), 'icon_custom', '0');
        }
    }

    public function remove(): void
    {
        $fileName = $this->getRelativePath();

        if ($this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }

        ilContainer::_writeContainerSetting($this->getObjId(), 'icon_custom', '0');
    }

    /**
     * @throws IOException
     */
    protected function createCustomIconDirectory(): void
    {
        $iconDirectory = $this->getIconDirectory();

        if (!$this->filesystem->has(dirname($iconDirectory))) {
            $this->filesystem->createDir(dirname($iconDirectory));
        }

        if (!$this->filesystem->has($iconDirectory)) {
            $this->filesystem->createDir($iconDirectory);
        }
    }

    protected function getIconDirectory(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->config->getBaseDirectory(),
            $this->config->getSubDirectoryPrefix() . $this->getObjId()
        ]);
    }

    protected function getIconFileName(): string
    {
        return self::ICON_BASENAME . '.' . $this->config->getTargetFileExtension();
    }

    protected function getRelativePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->getIconDirectory(),
            $this->getIconFileName()
        ]);
    }

    public function exists(): bool
    {
        if (!ilContainer::_lookupContainerSetting($this->getObjId(), 'icon_custom', '0')) {
            return false;
        }

        return $this->filesystem->has($this->getRelativePath());
    }

    public function getFullPath(): string
    {
        // TODO: Currently there is no option to get the relative base directory of a filesystem
        return implode(DIRECTORY_SEPARATOR, [
            ilFileUtils::getWebspaceDir(),
            $this->getRelativePath()
        ]);
    }

    public function createFromImportDir(string $source_dir): void
    {
        $target_dir = implode(DIRECTORY_SEPARATOR, [
            ilFileUtils::getWebspaceDir(),
            $this->getIconDirectory()
        ]);
        ilFileUtils::rCopy($source_dir, $target_dir);
        $this->persistIconState($this->getRelativePath());
    }
}
