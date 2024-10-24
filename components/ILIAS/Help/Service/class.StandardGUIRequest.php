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

namespace ILIAS\Help;

use ILIAS\Repository\BaseGUIRequest;

class StandardGUIRequest
{
    use BaseGUIRequest;

    public function __construct(
        \ILIAS\HTTP\Services $http,
        \ILIAS\Refinery\Factory $refinery,
        ?array $passed_query_params = null,
        ?array $passed_post_data = null
    ) {
        $this->initRequest(
            $http,
            $refinery,
            $passed_query_params,
            $passed_post_data
        );
    }

    public function getIds(): array
    {
        return $this->intArray("id");
    }

    public function getHelpModule(): string
    {
        return $this->str("help_module");
    }

    public function getHelpScreenId(): string
    {
        return $this->str("help_screen_id");
    }

    public function getHelpPage(): int
    {
        return $this->int("help_page");
    }

    public function getRefId(): int
    {
        return $this->int("ref_id");
    }

    public function getTerm(): string
    {
        return $this->str("term");
    }

    public function getHelpModuleId(): int
    {
        return $this->int("hm_id");
    }

    public function getHelpMode(): string
    {
        return $this->str("help_mode");
    }

    public function getOrder(): array
    {
        return $this->intArray("order");
    }
}
