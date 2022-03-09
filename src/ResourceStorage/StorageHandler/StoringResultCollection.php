<?php declare(strict_types=1);

namespace ILIAS\ResourceStorage\StorageHandler;

use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Revision\Revision;

/**
 * Class StoringResultCollection
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @internal
 */
class StoringResultCollection
{
    /**
     * @var ResourceIdentification
     */
    protected $resource_id;
    /**
     * @var StoringResult[]
     */
    protected $results = [];
    
    /**
     * @param ResourceIdentification $resource_id
     */
    public function __construct(ResourceIdentification $resource_id)
    {
        $this->resource_id = $resource_id;
    }
    
    public function getResourceIdentification() : ResourceIdentification
    {
        return $this->resource_id;
    }
    
    public function add(StoringResult $result) : void
    {
        $this->results[$result->getRevision()->getVersionNumber()] = $result;
    }
    
    public function getResults() : array
    {
        return $this->results;
    }
    
    public function hasResults() : bool
    {
        return count($this->results) > 0;
    }
    
    public function getAmountOfResults() : int
    {
        return count($this->results);
    }
    
    public function isFailed(Revision $revision) : bool
    {
        return isset($this->results[$revision->getVersionNumber()])
            && $this->results[$revision->getVersionNumber()]->isFailed();
    }
    
    public function hasFailed() : bool
    {
        foreach ($this->results as $result) {
            if ($result->isFailed()) {
                return true;
            }
        }
        return false;
    }
    
    public function hasAtLeastOneSuccessed() : bool
    {
        foreach ($this->results as $result) {
            if (!$result->isFailed()) {
                return true;
            }
        }
        return false;
    }
    
    public function getFailedSummary() : string
    {
        $summary = '';
        foreach ($this->results as $result) {
            if ($result->isFailed()) {
                $summary .= "Version {$result->getRevision()->getVersionNumber()}: " . $result->getMessage() . PHP_EOL;
            }
        }
        return $summary;
    }
}
