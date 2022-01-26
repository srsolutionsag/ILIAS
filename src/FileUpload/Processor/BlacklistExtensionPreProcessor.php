<?php

namespace ILIAS\FileUpload\Processor;

use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\FileUpload\DTO\ProcessingStatus;

/**
 * Class BlacklistExtensionPreProcessor
 * PreProcessor which denies all blacklisted file extensions.
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 * @since   5.3
 * @version 1.0.0
 */
class BlacklistExtensionPreProcessor implements PreProcessor
{

    private string $reason;
    /**
     * @var string[]
     */
    private array $blacklist;

    /**
     * BlacklistExtensionPreProcessor constructor.
     * Example:
     * ['jpg', 'svg', 'png', '']
     * Matches:
     * example.jpg
     * example.svg
     * example.png
     * example
     * No Match:
     * example.apng
     * example.png.exe
     * ...
     * @param \string[] $blacklist The file extensions which should be blacklisted.
     * @param string    $reason
     */
    public function __construct(array $blacklist, $reason = 'Extension is blacklisted.')
    {
        $this->blacklist = $blacklist;
        $this->reason = $reason;
    }

    /**
     * @inheritDoc
     */
    public function process(FileStream $stream, Metadata $metadata): \ILIAS\FileUpload\DTO\ProcessingStatus
    {
        if ($this->isBlacklisted($metadata, $stream)) {
            return new ProcessingStatus(ProcessingStatus::REJECTED, $this->reason);
        }

        return new ProcessingStatus(ProcessingStatus::OK, 'Extension is not blacklisted.');
    }

    /**
     * Checks if the current filename has a listed extension. (*.png, *.mp4 etc ...)
     * @return bool True if the extension is listed, otherwise false.
     */
    private function isBlacklisted(Metadata $metadata, FileStream $stream): bool
    {
        $filename = $metadata->getFilename();
        $extension = $this->getExtensionForFilename($filename);

        if (strtolower($extension) === 'zip') {
            $zip_file_path = $stream->getMetadata('uri');
            $zip = new \ZipArchive();
            $zip->open($zip_file_path);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $original_path = $zip->getNameIndex($i);
                $extension_sub_file = $this->getExtensionForFilename($original_path);
                if ($extension_sub_file === '') {
                    continue;
                }
                if (in_array($extension_sub_file, $this->blacklist, true)) {
                    $zip->close();
                    $this->reason = $this->reason .= " ($original_path in $filename)";

                    return true;
                }
            }
            $zip->close();
        }

        $in_array = in_array($extension, $this->blacklist, true);
        if (!$in_array) {
            $this->reason = $this->reason .= " ($filename)";
        }
        return $in_array;
    }

    /**
     * @param $filename
     * @return null|string
     */
    private function getExtensionForFilename($filename): string
    {
        $extensions = explode('.', $filename);

        $extension = count($extensions) <= 1 ? '' : strtolower(end($extensions));

        return $extension;
    }
}
