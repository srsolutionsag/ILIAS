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

namespace ILIAS;

use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Component;
use ILIAS\Component\Resource\OfComponent;
use ILIAS\Component\Resource\PublicAsset;
use ILIAS\File\Activities\CopyFile;
use ILIAS\File\Activities\CreateFile;
use ILIAS\File\Activities\DeleteFile;
use ILIAS\File\Activities\DeliverFileVersion;
use ILIAS\File\Activities\DownloadContainerAsZip;
use ILIAS\File\Activities\ListFileVersions;
use ILIAS\File\Activities\MoveFile;
use ILIAS\File\Activities\UpdateFileMetadata;
use ILIAS\File\Activities\DeliverFileObject;
use ILIAS\File\Activities\ListContainerContent;
use ILIAS\File\Activities\ListFiles;
use ILIAS\File\Activities\ReplaceFileContent;
use ILIAS\File\Activities\RepositoryProvider;
use ILIAS\FileDelivery\Activities\FilePayloadFactory;
use ILIAS\ResourceStorage\Activities\DeliverResource;
use ILIAS\ResourceStorage\Activities\ServiceProvider as StorageProvider;
use ILIAS\FileUpload\Activities\FileParameter;
use ILIAS\Setup\Agent;
use ILIAS\Refinery\Factory;

class File implements Component
{
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        $contribute[Agent::class] = static fn(): \ilFileObjectAgent =>
            new \ilFileObjectAgent(
                $pull[Factory::class]
            );

        $contribute[PublicAsset::class] = fn(): PublicAsset =>
            new OfComponent($this, "default_file_icons", "assets");

        /**
         * Creating a file object is one activity, not a sequence of calls: the
         * file comes with the call, the object is created, put into the tree and
         * given its content - or nothing is left behind.
         */
        $internal[RepositoryProvider::class] = static fn(): RepositoryProvider => new RepositoryProvider();

        $internal[CreateFile::class] = static fn(): CreateFile => new CreateFile(
            $pull[FileParameter::class],
            $internal[RepositoryProvider::class],
        );

        $internal[ReplaceFileContent::class] = static fn(): ReplaceFileContent => new ReplaceFileContent(
            $pull[FileParameter::class],
            $internal[RepositoryProvider::class],
        );

        /**
         * Listing what is below a node is a matter of the repository, not of
         * this component, so it is not contributed as an Activity. It stays as
         * code, because listing files is that listing plus what a file is.
         */
        $internal[ListContainerContent::class] = static fn(): ListContainerContent => new ListContainerContent(
            $internal[RepositoryProvider::class],
        );

        $internal[ListFiles::class] = static fn(): ListFiles => new ListFiles(
            $internal[RepositoryProvider::class],
            $internal[ListContainerContent::class],
        );

        /**
         * Reading a file of the repository is guarded by the read permission on
         * the object; the resource behind it is then delivered by the according
         * activity of the ResourceStorage, which trusts this check.
         */
        $internal[DeliverFileObject::class] = static fn(): DeliverFileObject => new DeliverFileObject(
            $internal[RepositoryProvider::class],
            $pull[DeliverResource::class],
            $pull[FilePayloadFactory::class],
        );

        /**
         * Everything else a file object can do: its data, its versions, where it
         * lives, and whether it lives at all.
         */
        $internal[UpdateFileMetadata::class] = static fn(): UpdateFileMetadata => new UpdateFileMetadata(
            $internal[RepositoryProvider::class],
        );

        $internal[DeleteFile::class] = static fn(): DeleteFile => new DeleteFile(
            $internal[RepositoryProvider::class],
        );

        $internal[MoveFile::class] = static fn(): MoveFile => new MoveFile(
            $internal[RepositoryProvider::class],
        );

        $internal[CopyFile::class] = static fn(): CopyFile => new CopyFile(
            $internal[RepositoryProvider::class],
        );

        $internal[ListFileVersions::class] = static fn(): ListFileVersions => new ListFileVersions(
            $internal[RepositoryProvider::class],
            $pull[StorageProvider::class],
        );

        $internal[DeliverFileVersion::class] = static fn(): DeliverFileVersion => new DeliverFileVersion(
            $internal[RepositoryProvider::class],
            $pull[StorageProvider::class],
            $pull[FilePayloadFactory::class],
        );

        /**
         * Packing a container performs the listing and the delivery of every
         * single file, so it cannot pack what a user may not read.
         */
        $internal[DownloadContainerAsZip::class] = static fn(): DownloadContainerAsZip =>
            new DownloadContainerAsZip(
                $internal[ListFiles::class],
                $internal[DeliverFileObject::class],
                $pull[FilePayloadFactory::class],
            );

        $provide[CreateFile::class] = static fn(): CreateFile => $internal[CreateFile::class];
        $provide[ReplaceFileContent::class] = static fn(): ReplaceFileContent =>
            $internal[ReplaceFileContent::class];
        $provide[ListFiles::class] = static fn(): ListFiles => $internal[ListFiles::class];
        $provide[ListContainerContent::class] = static fn(): ListContainerContent =>
            $internal[ListContainerContent::class];
        $provide[DeliverFileObject::class] = static fn(): DeliverFileObject =>
            $internal[DeliverFileObject::class];

        $contribute[Activity::class] = static fn(): Activity => $internal[CreateFile::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[ReplaceFileContent::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[ListFiles::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[DeliverFileObject::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[UpdateFileMetadata::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[DeleteFile::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[MoveFile::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[CopyFile::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[ListFileVersions::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[DeliverFileVersion::class];
        $contribute[Activity::class] = static fn(): Activity => $internal[DownloadContainerAsZip::class];
    }
}
