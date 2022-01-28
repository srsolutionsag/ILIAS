<?php

namespace ILIAS\FileUpload\Processor;

use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\ScalarTypeCheckAware;
use Psr\Http\Message\StreamInterface;

/**
 * Class BlacklistFileHeaderPreProcessor
 *
 * The blacklist file header pre processor rejects all files which begin with the specified file start.
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 * @since 5.3
 * @version 1.0.0
 */
final class BlacklistFileHeaderPreProcessor implements PreProcessor
{
    use ScalarTypeCheckAware;

    private string $fileHeader;
    private int $fileHeaderLength;


    /**
     * BlacklistFileHeaderPreProcessor constructor.
     */
    public function __construct(string $fileHeader)
    {
        $this->stringTypeCheck($fileHeader, 'fileHeader');

        $this->fileHeaderLength = strlen($fileHeader);
        $this->fileHeader = $fileHeader;
    }


    /**
     * @inheritDoc
     */
    public function process(FileStream $stream, Metadata $metadata): ProcessingStatus
    {
        $header = $stream->read($this->fileHeaderLength);
        if (strcmp($this->fileHeader, $header) !== 0) {
            return new ProcessingStatus(ProcessingStatus::OK, 'File header does not match blacklist.');
        }

        return new ProcessingStatus(ProcessingStatus::REJECTED, 'File header matches blacklist.');
    }
}
