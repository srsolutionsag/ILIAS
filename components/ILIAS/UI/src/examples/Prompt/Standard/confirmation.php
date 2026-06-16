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

namespace ILIAS\UI\examples\Prompt\Standard;

use ILIAS\Filesystem\Stream\Streams;
use ILIAS\UI\URLBuilder;

/**
 * ---
 * description: >
 *   ...
 *
 * expected output: >
 *   ...
 * ---
 */
function confirmation(): string
{
    global $DIC;

    $http = $DIC->http();
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $get_request = $http->wrapper()->query();
    $post_request = $http->wrapper()->post();
    $data_factory = new \ILIAS\Data\Factory();
    $refinery_factory = new \ILIAS\Refinery\Factory($data_factory, $DIC->language());

    $example_uri = $data_factory->uri((string) $http->request()->getUri());
    $base_url_builder = new URLBuilder($example_uri);

    [$get_entities_url, $get_entity_flag] = $base_url_builder->acquireParameter(
        explode('\\', __NAMESPACE__),
        "confirm"
    );
    [$post_entities_url, $post_entity_flag] = $base_url_builder->acquireParameter(
        explode('\\', __NAMESPACE__),
        "process"
    );
    [$post_entities_url, $post_entity_payload] = $base_url_builder->acquireParameter(
        explode('\\', __NAMESPACE__),
        "entities"
    );

    // simulates async GET endpoint:
    if ($get_request->has($get_entity_flag->getName())) {
        $state = $factory->prompt()->state()->confirm(
            [
                $factory->entity()->standard(1, "Entity 1", ""),
                $factory->entity()->standard(2, "Entity 2", ""),
                $factory->entity()->standard(3, "Entity 3", ""),
                // ...
            ],
            'Are you sure you want to perform this action?',
            $post_entities_url,
            $post_entity_payload,
        )->withTitle('Performing some action');

        $html = $renderer->renderAsync($state);
        $http->saveResponse(
            $http->response()
                 ->withHeader('Content-Type', 'text/html; charset=utf-8')
                 ->withBody(Streams::ofString($html))
        );
        $http->sendResponse();
        $http->close();
    }

    // simulates sync POST endpoint:
    if ($get_request->has($post_entity_flag->getName())) {
        $data = $post_request->retrieve(
            $post_entity_payload->getName(),
            $refinery_factory->kindlyTo()->listOf($refinery_factory->kindlyTo()->string())
        );
    } else {
        $data = [];
    }

    $prompt = $factory->prompt()->standard($get_entities_url->buildURI());
    $trigger = $factory->button()->primary('open', $prompt->getShowSignal());

    return '<pre>' . var_export($data, true) . '</pre>' .
        $renderer->render([$prompt, $trigger]);
}
