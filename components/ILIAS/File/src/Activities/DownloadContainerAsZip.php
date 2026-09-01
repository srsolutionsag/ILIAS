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

namespace ILIAS\File\Activities;

use ILIAS\Component\Activities\Query;
use ILIAS\Data\Description;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\Result;
use ILIAS\Data\Text\SimpleDocumentMarkdown;
use ILIAS\FileDelivery\Activities\FilePayload;
use ILIAS\FileDelivery\Activities\FilePayloadFactory;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Factory as InputFactory;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;

/**
 * Deliver the files of a container as one zip archive.
 *
 * Nothing here decides who may see what: `ListFiles` already answers that, and
 * `DeliverFileObject` checks the permission per file. This activity performs
 * both and only does the packing - which is why a user can never zip their way
 * around a permission.
 */
class DownloadContainerAsZip extends Query
{
    private const int MAX_FILES = 500;

    public function __construct(
        private readonly ListFiles $list_files,
        private readonly DeliverFileObject $deliver_file,
        private readonly FilePayloadFactory $payloads,
        private readonly DataFactory $data = new DataFactory(),
    ) {
    }

    public function getDescription(): SimpleDocumentMarkdown
    {
        return $this->data->text()->markdown()->simpleDocument(
            "Packs the files of a container into a zip archive.\n\n"
            . "Only files the user may read end up in it, and at most " . self::MAX_FILES . " of them. "
            . "The archive is returned base64 encoded; over REST `?raw=1` gives the zip itself."
        );
    }

    public function getInputDescription(FieldFactory $f): FormInput
    {
        return $f->section(
            [
                'parent_ref_id' => $f->numeric('Container', 'Reference id of the container to pack.')
                    ->withRequired(true)
                    ->withDedicatedName('parent_ref_id'),
                'recursive' => $f->checkbox(
                    'Recursive',
                    'Also pack the files of the containers below this one.'
                )->withDedicatedName('recursive'),
            ],
            'Download a container as zip'
        );
    }

    public function getOutputDescription(Description\Factory $f): Description\Description
    {
        return $this->payloads->getDescription($f, 'The zip archive with the files of the container.');
    }

    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        // the listing decides who may look into the container
        return $this->list_files->isAllowedToPerform($usr_id, $parameters);
    }

    /**
     * @param array{parent_ref_id: int, recursive: bool, usr_id: int} $parameters
     */
    public function perform(mixed $parameters): FilePayload
    {
        $files = $this->list_files->perform($parameters);

        if (count($files) > self::MAX_FILES) {
            throw new \DomainException(
                'This container holds more than ' . self::MAX_FILES . ' files, refusing to pack it.',
                422
            );
        }

        $name = 'container_' . $parameters['parent_ref_id'] . '.zip';

        // ZipArchive works on a path, so this is built outside of the ILIAS
        // filesystems and read back in one go
        $path = tempnam(sys_get_temp_dir(), 'ilias_zip_');

        if ($path === false) {
            throw new \RuntimeException('The archive could not be created.');
        }

        try {
            $this->pack($path, $files);

            return $this->payloads->fromString($name, 'application/zip', (string) file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    public function maybePerformAs(
        InputFactory $input_factory,
        int $usr_id,
        array $raw_parameters
    ): Result {
        try {
            $parameters = $this->toParameters($raw_parameters);

            if (!$this->isAllowedToPerform($usr_id, $parameters)) {
                return $this->data->error(
                    new \DomainException('You are not allowed to look into this container.', 403)
                );
            }

            return $this->data->ok($this->perform([...$parameters, 'usr_id' => $usr_id]));
        } catch (\Throwable $e) {
            return $this->data->error($e);
        }
    }

    /**
     * @param list<FileEntry> $files
     */
    private function pack(string $path, array $files): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('The archive could not be created.');
        }

        $names = [];

        foreach ($files as $file) {
            // the listing has already dropped everything the user may not read,
            // so the content may be taken through the internal door
            $payload = $this->deliver_file->perform(['ref_id' => $file->ref_id]);

            $zip->addFromString(
                $this->uniqueName($names, $payload->filename),
                (string) base64_decode($payload->content, true)
            );
        }

        $zip->close();
    }

    /**
     * @param array<string, int> $names
     */
    private function uniqueName(array &$names, string $filename): string
    {
        if (!isset($names[$filename])) {
            $names[$filename] = 1;

            return $filename;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $name = $base . ' (' . ++$names[$filename] . ')' . ($extension === '' ? '' : '.' . $extension);

        return $name;
    }

    /**
     * @param array<string, mixed> $raw_parameters
     *
     * @return array{parent_ref_id: int, recursive: bool}
     */
    private function toParameters(array $raw_parameters): array
    {
        $parent_ref_id = (int) ($raw_parameters['parent_ref_id'] ?? 0);

        if ($parent_ref_id <= 0) {
            throw new \InvalidArgumentException('A container is required.', 400);
        }

        return [
            'parent_ref_id' => $parent_ref_id,
            'recursive' => filter_var($raw_parameters['recursive'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }
}
