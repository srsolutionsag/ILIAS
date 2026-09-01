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

use ILIAS\Component\Component;
use ILIAS\Setup\Agent;
use ILIAS\Refinery\Factory;
use ILIAS\FileDelivery\Activities\FilePayloadFactory;
use ILIAS\ResourceStorage\Activities\DeliverResource;
use ILIAS\ResourceStorage\Activities\ServiceProvider;

class ResourceStorage implements Component
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
        $contribute[Agent::class] = static fn(): \ilResourceStorageSetupAgent =>
            new \ilResourceStorageSetupAgent(
                $pull[Factory::class]
            );

        $internal[ServiceProvider::class] = static fn(): ServiceProvider => new ServiceProvider();

        /**
         * Delivering a resource builds the same payload every other delivering
         * activity builds, so all of them look alike to a consumer.
         */
        $internal[DeliverResource::class] = static fn(): DeliverResource => new DeliverResource(
            $internal[ServiceProvider::class],
            $pull[FilePayloadFactory::class],
        );

        /**
         * Components that own something stored in here need the facade to read
         * versions of their resources - see ILIAS\File\Activities.
         */
        $provide[ServiceProvider::class] = static fn(): ServiceProvider => $internal[ServiceProvider::class];

        /**
         * This is not contributed as an Activity: a resource identification is
         * not a domain address, nobody asks for "the resource 7d654653-...". It
         * is the storage API of this component, offered as code to the activities
         * that own the things stored here - those know who may see what and check
         * it themselves.
         */
        $provide[DeliverResource::class] = static fn(): DeliverResource => $internal[DeliverResource::class];
    }
}
