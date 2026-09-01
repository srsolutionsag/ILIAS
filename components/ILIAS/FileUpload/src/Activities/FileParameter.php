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

namespace ILIAS\FileUpload\Activities;

use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;

/**
 * A file as a parameter of an activity: how it is described, and how it is read.
 *
 * Every activity that takes a file uses this, so all of them look the same from
 * outside and none of them handles bytes on its own. It describes two fields -
 * the name of the file and its content, base64 encoded - and turns them back
 * into a FileContent the activity can work with.
 *
 * Base64 is the wire format because it is the one every transport can carry:
 * JSON knows no bytes, and SOAP encodes them the same way. The REST service
 * accepts a multipart upload as well and encodes it before the activity sees
 * it, which costs the activity nothing.
 *
 * The limits belong here too. A file field of the UI framework carries a
 * maximum size and a list of accepted mime types, and an activity taking a file
 * should be able to say the same - hence withMaxFileSize() and
 * withAcceptedMimeTypes(), named after their counterparts in the UI.
 */
class FileParameter
{
    public const string DEFAULT_CONTENT_NAME = 'content';
    public const string DEFAULT_FILENAME_NAME = 'filename';

    /**
     * @param string[] $accepted_mime_types
     */
    public function __construct(
        private readonly TempFileStore $files,
        private readonly string $content_name = self::DEFAULT_CONTENT_NAME,
        private readonly string $filename_name = self::DEFAULT_FILENAME_NAME,
        private readonly int $max_file_size = 0,
        private readonly array $accepted_mime_types = [],
    ) {
    }

    /**
     * Use different parameter names, e.g. for an activity taking two files.
     */
    public function withNames(string $content_name, string $filename_name): self
    {
        return new self(
            $this->files,
            $content_name,
            $filename_name,
            $this->max_file_size,
            $this->accepted_mime_types
        );
    }

    /**
     * @param int $size_in_bytes 0 means no limit of our own, the limits of the
     *                           installation still apply
     */
    public function withMaxFileSize(int $size_in_bytes): self
    {
        return new self(
            $this->files,
            $this->content_name,
            $this->filename_name,
            $size_in_bytes,
            $this->accepted_mime_types
        );
    }

    public function getMaxFileSize(): int
    {
        return $this->max_file_size;
    }

    /**
     * @param string[] $mime_types an empty list accepts anything
     */
    public function withAcceptedMimeTypes(array $mime_types): self
    {
        return new self(
            $this->files,
            $this->content_name,
            $this->filename_name,
            $this->max_file_size,
            array_values(array_map(strtolower(...), $mime_types))
        );
    }

    /**
     * @return string[]
     */
    public function getAcceptedMimeTypes(): array
    {
        return $this->accepted_mime_types;
    }

    /**
     * The fields describing this file, to be merged into the input description
     * of the activity.
     *
     * @return array<string, FormInput>
     */
    public function describe(FieldFactory $f, string $label = 'File', ?string $purpose = null): array
    {
        return [
            $this->filename_name => $f->text(
                $label . ' name',
                'Name of the file, including its suffix.'
            )->withRequired(true)->withDedicatedName($this->filename_name),

            $this->content_name => $f->textarea(
                $label,
                $this->byline($purpose)
            )->withRequired(true)->withDedicatedName($this->content_name),
        ];
    }

    /**
     * Reads the file out of the raw parameters of a request.
     *
     * The content is put into the temp storage right here, because that is
     * where it has to be for anything to store it. Whoever calls this owns the
     * result and has to release() it.
     *
     * @param array<string, mixed> $raw_parameters
     */
    public function read(array $raw_parameters): FileContent
    {
        $filename = trim((string) ($raw_parameters[$this->filename_name] ?? ''));

        if ($filename === '') {
            throw new \InvalidArgumentException('A file name is required.', 400);
        }

        $encoded = $raw_parameters[$this->content_name] ?? null;

        if (!is_string($encoded) || $encoded === '') {
            throw new \InvalidArgumentException('The content of the file is required.', 400);
        }

        // refuse before decoding: base64 is a third larger than what it carries
        if ($this->max_file_size > 0 && (strlen($encoded) / 4) * 3 > $this->max_file_size + 3) {
            throw $this->tooLarge();
        }

        $content = base64_decode($encoded, true);

        if ($content === false) {
            throw new \InvalidArgumentException('The content is not valid base64.', 400);
        }

        if ($this->max_file_size > 0 && strlen($content) > $this->max_file_size) {
            throw $this->tooLarge();
        }

        return $this->accept($this->files->store($filename, $content));
    }

    private function accept(string $handle): FileContent
    {
        $file = new FileContent(
            $this->files,
            $handle,
            $this->files->getFilename($handle),
            $this->files->getMimeType($handle),
            $this->files->getSize($handle),
        );

        if ($this->accepted_mime_types !== [] && !in_array($file->mime_type, $this->accepted_mime_types, true)) {
            $file->release();

            throw new \InvalidArgumentException(
                "Files of the type '{$file->mime_type}' are not accepted here.",
                400
            );
        }

        return $file;
    }

    private function tooLarge(): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            "The file is larger than the {$this->max_file_size} bytes accepted here.",
            400
        );
    }

    private function byline(?string $purpose): string
    {
        $byline = $purpose ?? 'The content of the file';
        $byline = rtrim($byline, '.') . ', base64 encoded.';

        if ($this->max_file_size > 0) {
            $byline .= " At most {$this->max_file_size} bytes.";
        }

        if ($this->accepted_mime_types !== []) {
            $byline .= ' Accepted types: ' . implode(', ', $this->accepted_mime_types) . '.';
        }

        return $byline;
    }
}
