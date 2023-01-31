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

use ILIAS\ResourceStorage\Flavour\Definition\FlavourDefinition;
use ILIAS\ResourceStorage\Flavour\Definition\PagesToExtract;
use ILIAS\ResourceStorage\Flavour\Engine\Engine;
use ILIAS\ResourceStorage\Flavour\Engine\ImagickEngine;

/**
 * @deprecated Use IRSS Flavours instead
 */
class ilPreview
{
    private static array $instances = [];
    // status values
    public const RENDER_STATUS_NONE = "none";
    public const RENDER_STATUS_PENDING = "pending";
    public const RENDER_STATUS_CREATED = "created";
    public const RENDER_STATUS_FAILED = "failed";

    private ilObjFile $object;
    private \ILIAS\ResourceStorage\Flavour\Flavours $flavours;
    private ?\ILIAS\ResourceStorage\Identification\ResourceIdentification $rid = null;
    private \ILIAS\ResourceStorage\Consumer\Consumers $consumers;
    private FlavourDefinition $definition;
    private bool $supported = false;
    private ilPreviewSettings $settings;

    public static function getInstance(int $object_id): self
    {
        if (!isset(self::$instances[$object_id])) {
            self::$instances[$object_id] = new self($object_id);
        }
        return self::$instances[$object_id];
    }



    /**
     * @deprecated use IRSS Flavours instead
     */
    private function __construct(int $a_obj_id)
    {
        global $DIC;

        $object = ilObjectFactory::getInstanceByObjId($a_obj_id, false);
        if (!$object instanceof ilObjFile) {
            throw new InvalidArgumentException(
                "ilPreview: No file-object with id $a_obj_id found."
            );
        }
        $this->object = $object;

        $this->settings = ilPreviewSettings::getInstance();

        $this->rid = $DIC->resourceStorage()->manage()->find($this->object->getResourceId());
        $this->flavours = $DIC->resourceStorage()->flavours();
        $this->consumers = $DIC->resourceStorage()->consume();
        $this->definition = new PagesToExtract(
            true,
            $this->settings->getImageSize(),
            $this->settings->getMaximumPreviews(),
            true
        );
        if ($this->rid !== null) {
            $this->supported = $this->flavours->possible($this->rid, $this->definition);
            $this->flavours->remove($this->rid, $this->definition);
        }
    }


    /**
     * Determines whether the object with the specified reference id has a preview.
     *
     * @param int $a_obj_id The id of the object to check.
     * @param string $a_type The type of the object to check.
     * @return bool true, if the object has a preview; otherwise, false.
     */
    public static function hasPreview(int $a_obj_id, string $a_type): bool
    {
        static $settings;
        if (!isset($settings)) {
            $settings = ilPreviewSettings::getInstance();
        }

        if (!$settings->isPreviewEnabled()) {
            return false;
        }

        if ($a_type !== "file") {
            return false;
        }

        return self::getInstance($a_obj_id)->isSupported();
    }

    public static function lookupRenderStatus(int $a_obj_id): string
    {
        return self::hasPreview($a_obj_id, "file") ? self::RENDER_STATUS_CREATED : self::RENDER_STATUS_NONE;
    }

    public function getRenderStatus(): string
    {
        return self::lookupRenderStatus($this->object->getId());
    }


    public function isSupported(): bool
    {
        return $this->supported;
    }


    public function getImages(): array
    {
        $preview = $this->flavours->get(
            $this->rid,
            $this->definition
        );

        return array_map(
            function (string $url): array {
                return [
                    'url' => $url,
                    'width' => $this->settings->getImageSize(),
                    'height' => $this->settings->getImageSize()
                ];
            },
            $this->consumers->flavourUrls($preview)->getURLsAsArray(true)
        );
    }
}
