<?php declare(strict_types=1);

namespace ILIAS\ResourceStorage\StorageHandler;

use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Revision\Revision;

/**
 * Class StoringResult
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @internal
 */
class StoringResult
{
    private const SUCCESS = 1;
    private const FAILED = 2;
    /**
     * @var ResourceIdentification
     */
    protected $resource_id;
    /**
     * @var Revision
     */
    protected $revision;
    /**
     * @var int
     */
    protected $status = self::SUCCESS;
    /**
     * @var string
     */
    protected $message = '';
    
    /**
     * @param Revision $revision
     */
    public function __construct(Revision $revision)
    {
        $this->revision = $revision;
    }
    
    public function setFailed(string $message) : void
    {
        $this->status = self::FAILED;
        $this->message = $message;
    }
    
    public function isFailed() : bool
    {
        return $this->status === self::FAILED;
    }
    
    public function getRevision() : Revision
    {
        return $this->revision;
    }
    
    public function getMessage() : string
    {
        return $this->message;
    }
}
