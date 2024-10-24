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

namespace ILIAS\SurveyQuestionPool;

use ILIAS\DI\Container;
use ILIAS\Repository\GlobalDICGUIServices;

class InternalGUIService
{
    use GlobalDICGUIServices;

    protected static array $instance = [];

    public function __construct(
        Container $DIC,
        protected InternalDataService $data_service,
        protected InternalDomainService $domain_service
    ) {
        $this->initGUIServices($DIC);
    }

    public function editing(): Editing\GUIService
    {
        return self::$instance["editing"] ??= new Editing\GUIService(
            $this,
            $this->domain_service
        );
    }

    public function settings(
    ): Settings\GUIService {
        return self::$instance["settings"] ??= new Settings\GUIService(
            $this->data_service,
            $this->domain_service,
            $this
        );
    }
}
