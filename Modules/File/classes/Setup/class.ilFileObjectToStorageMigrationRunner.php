<?php

use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileUpload\Location;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\Resource\ResourceBuilder;
use ILIAS\ResourceStorage\Lock\LockHandlerilDB;
use ILIAS\ResourceStorage\Consumer\ConsumerFactory;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\ResourceStorage\Resource\StorableResource;
use ILIAS\Filesystem\Filesystem;
use ILIAS\ResourceStorage\Policy\FileNamePolicyException;
use ILIAS\ResourceStorage\StorageHandler\FileSystemStorageHandlerV2;
use ILIAS\ResourceStorage\StorageHandler\FileSystemBased\MaxNestingFileSystemStorageHandler;
use ILIAS\ResourceStorage\Information\Repository\InformationDBRepository;
use ILIAS\ResourceStorage\Revision\Repository\RevisionDBRepository;
use ILIAS\ResourceStorage\Resource\Repository\ResourceDBRepository;
use ILIAS\ResourceStorage\Stakeholder\Repository\StakeholderDBRepository;
use ILIAS\ResourceStorage\Preloader\StandardRepositoryPreloader;
use ILIAS\Setup\CLI\IOWrapper;
use ILIAS\ResourceStorage\StorageHandler\StoringResult;

class ilFileObjectToStorageMigrationRunner
{
    const SUCCESS = 'success';
    const FAILED = 'failed';
    const LEGACY_PATH = 'path';
    const ADDITIONAL_INFO = 'additional_info';
    const STATUS = 'status';
    /**
     * @var string
     */
    protected $movement_implementation;
    
    /**
     * @var ConsumerFactory
     */
    protected $consumer_factory;
    /**
     * @var Manager
     */
    protected $storage_manager;
    /**
     * @var ResourceBuilder
     */
    protected $resource_builder;
    /**
     * @var false|resource
     */
    protected $migration_log_handle;
    /**
     * @var Filesystem
     */
    protected $file_system;
    /**
     * @var ilDBInterface
     */
    protected $database;
    /**
     * @var bool
     */
    protected $keep_originals = false;
    /**
     * @var null|int
     */
    protected $migrate_to_new_object_id = null;
    /**
     * @var ilObjFileStakeholder
     */
    protected $stakeholder;
    
    /**
     * ilFileObjectToStorageMigration constructor.
     *
     * @param Filesystem $file_system
     * @param ilDBInterface $database
     */
    public function __construct(Filesystem $file_system, ilDBInterface $database, string $log_file_path)
    {
        $this->file_system = $file_system;
        $this->database = $database;
        $this->migration_log_handle = fopen($log_file_path, 'ab');
        
        $storage_handler = new MaxNestingFileSystemStorageHandler($this->file_system, Location::STORAGE, true);
        $storage_handler_factory = new StorageHandlerFactory([
            $storage_handler
        ]);
        
        $this->movement_implementation = $storage_handler->movementImplementation();
        
        $revisionDBRepository = new RevisionDBRepository($database);
        $resourceDBRepository = new ResourceDBRepository($database);
        $informationDBRepository = new InformationDBRepository($database);
        $stakeholderDBRepository = new StakeholderDBRepository($database);
        $builder = new ResourceBuilder(
            $storage_handler_factory,
            $revisionDBRepository,
            $resourceDBRepository,
            $informationDBRepository,
            $stakeholderDBRepository,
            new LockHandlerilDB($database)
        );
        $this->resource_builder = $builder;
        $this->storage_manager = new Manager(
            $builder, new StandardRepositoryPreloader(
                $resourceDBRepository,
                $revisionDBRepository,
                $informationDBRepository,
                $stakeholderDBRepository
            )
        );
        $this->consumer_factory = new ConsumerFactory($storage_handler_factory);
        $this->stakeholder = new ilObjFileStakeholder();
    }
    
    private function appendVersionAsRevision(ilFileObjectToStorageVersion $version, StorableResource $resource)
    {
        $stream = Streams::ofResource(fopen($version->getPath(), 'rb'));
        $info_resolver = new ilFileObjectToStorageInfoResolver(
            $stream,
            $version->getVersion(),
            $version->getOwner(),
            $version->getTitle(),
            (new DateTimeImmutable())->setTimestamp($version->getCreationDateTimestamp())
        );
        $this->resource_builder->appendFromStream(
            $resource,
            $stream,
            $info_resolver,
            $this->keep_originals
        );
    }
    
    /**
     * @return bool the item has been migrated
     */
    public function migrate(ilFileObjectToStorageDirectory $item, IOWrapper $io) : bool
    {
        $has_been_migrated = true;
        $resource = $this->getResource($item);
        $object_id = $this->getMigrateToNewObjectId() ?? $item->getObjectId();
        $logging_infos = [];
        foreach ($item->getVersions() as $version) {
            try {
                $status = self::SUCCESS;
                $aditional_info = '';
                $this->appendVersionAsRevision($version, $resource);
            } catch (Throwable $t) {
                $status = self::FAILED;
                $aditional_info = $t->getMessage();
            }
            $logging_infos[$version->getVersion()] = [
                self::STATUS => $status,
                self::ADDITIONAL_INFO => $aditional_info,
                self::LEGACY_PATH => $version->getPath()
            ];
        }
        $resource->addStakeholder($this->stakeholder);
        try {
            $results = $this->resource_builder->store($resource);
            foreach ($results->getResults() as $result) {
                $revision_version_number = $result->getRevision()->getVersionNumber();
                $reading_status = $logging_infos[$revision_version_number][self::STATUS] ?? self::FAILED;
                $writing_status = $result->isFailed() ? self::FAILED : self::SUCCESS;
                $overall_status = $reading_status === self::SUCCESS && $writing_status === self::SUCCESS ? self::SUCCESS : self::FAILED;
                
                $this->logMigratedFile(
                    $object_id,
                    $resource->getIdentification()->serialize(),
                    $revision_version_number,
                    $logging_infos[$revision_version_number][self::LEGACY_PATH] ?? 'unknownpath',
                    $overall_status,
                    $this->movement_implementation,
                    $logging_infos[$revision_version_number][self::ADDITIONAL_INFO] ?? ''
                );
            }
            
            if ($results->hasAtLeastOneSuccessed()) {
                $this->database->manipulateF(
                    'UPDATE file_data SET rid = %s WHERE file_id = %s',
                    ['text', 'integer'],
                    [$resource->getIdentification()->serialize(), $object_id]
                );
            } elseif ($item->hasParsingFailed()) {
                $has_been_migrated = false;
                $io->error('Could not parse directory ' . $item->getPath());
            } else {
                $has_been_migrated = false;
                if ($results->hasResults()) {
                    $io->error('Errors migrating ' . $item->getPath() . ': ' . $results->getFailedSummary());
                } else {
                    $io->error('No File-Version migrated from ' . $item->getPath());
                }
            }
        } catch (FileNamePolicyException $e) {
            // continue
        }
        
        if (null === $this->getMigrateToNewObjectId() && $has_been_migrated) {
            $item->tearDown();
        }
        
        return $has_been_migrated;
    }
    
    private function logMigratedFile(
        int $object_id,
        string $rid,
        int $version,
        string $old_path,
        string $status,
        string $movement_implementation,
        string $aditional_info = null
    ) : void {
        fputcsv($this->migration_log_handle, [
            $object_id,
            $old_path,
            $rid,
            $version,
            $status,
            $movement_implementation,
            $aditional_info,
            date(DATE_ATOM)
        ], ";");
    }
    
    /**
     * @param ilFileObjectToStorageDirectory $item
     * @return StorableResource
     * @throws \ILIAS\ResourceStorage\Resource\ResourceNotFoundException
     */
    private function getResource(
        ilFileObjectToStorageDirectory $item
    ) : StorableResource {
        $r = $this->database->queryF(
            "SELECT rid FROM file_data WHERE file_id = %s",
            ['integer'],
            [$item->getObjectId()]
        );
        $d = $this->database->fetchObject($r);
        
        if (isset($d->rid) && $d->rid !== '' && ($resource_identification = $this->storage_manager->find(
                $d->rid
            )) && $resource_identification !== null) {
            $resource = $this->resource_builder->get($resource_identification);
        } else {
            $resource = $this->resource_builder->newBlank();
        }
        return $resource;
    }
    
    /**
     * @return int|null
     */
    public function getMigrateToNewObjectId() : ?int
    {
        return $this->migrate_to_new_object_id;
    }
    
    /**
     * @param int|null $migrate_to_new_object_id
     * @return ilFileObjectToStorageMigrationRunner
     */
    public function setMigrateToNewObjectId(?int $migrate_to_new_object_id) : ilFileObjectToStorageMigrationRunner
    {
        $this->migrate_to_new_object_id = $migrate_to_new_object_id;
        $this->keep_originals = true;
        return $this;
    }
    
}
