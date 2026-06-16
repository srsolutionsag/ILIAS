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
use ILIAS\UI\Component\Listing\Entity\EntityRetrieval;
use ILIAS\UI\Component\Entity\Entity;

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

    [$get_entities_url, $get_entities] = $base_url_builder->acquireParameter(
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
    if ($get_request->has($get_entities->getName())) {
        $state = $factory->prompt()->state()->confirm(
            new ConfirmationEntityRetrieval(),
            $post_entities_url,
            $post_entity_payload,
            $get_request->retrieve(
                $get_entities->getName(),
                $refinery_factory->kindlyTo()->listOf($refinery_factory->kindlyTo()->int())
            ),
            'Are you sure you want to perform this action?',
            'Performing some action',
        );

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
            $refinery_factory->kindlyTo()->listOf($refinery_factory->kindlyTo()->int())
        );
    } else {
        $data = [];
    }

    $prompt = $factory->prompt()->standard($get_entities_url->buildURI());
    $trigger = $factory->button()->primary('open', $prompt->getShowSignal());

    return '<pre>' . var_export($data, true) . '</pre>' .
        $renderer->render([$prompt, $trigger]);
}

/** @noinspection AutoloadingIssuesInspection */
class ConfirmationEntityRetrieval implements EntityRetrieval
{
    public function getEntities(
        \ILIAS\UI\Factory $ui_factory,
        \ILIAS\Data\Range $range,
        \ILIAS\Data\Order $order,
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters,
    ): \Generator {
        yield $this->getPseudoEntity($ui_factory, '1.1');
        yield $this->getPseudoEntity($ui_factory, '1.2');
        yield $this->getPseudoEntity($ui_factory, '1.3');
    }

    public function getEntitiesByIds(\ILIAS\UI\Factory $ui_factory, array $entity_ids): \Generator
    {
        foreach ($entity_ids as $entity_id) {
            yield $this->getPseudoEntity($ui_factory, $entity_id);
        }
    }

    protected function getPseudoEntity(\ILIAS\UI\Factory $ui_factory, string $entity_id): Entity
    {
        return $ui_factory->entity()->standard($entity_id, "Entity $entity_id", "");
    }
}
