<?php

declare(strict_types=1);

namespace ILIAS\UI\Examples\Entity\Standard;

/**
 * ---
 * description: >
 *   Full example showing how the entity could be used with all of its features.
 *
 * expected output: >
 *   Entities arrange information about e.g. an object into semantic groups;
 *   this example focusses on the possible contents of those groups and shows
 *   a possible representation of a made up event.
 *   From top to bottom, left to right:
 *   - There is a precondition; it links to ilias.de.
 *   - An action-dropdown is available with two entries linking to ilias/github.
 *   - An icon indents the following.
 *   - Prominently featured is the event's date proptery.
 *   - Only after that, the title of the event is displayed in bold.
 *   - A progress meter ("in progress") is followed by detailed properties:
 *     - Room information
 *     - Description
 *     - in one line: Available seats and availability of the event
 *     - in the next line: duration and the information of available redording
 *   - The bottom "row" shows two tags on the left
 *   - and two glyphs on the right, the first one with status counter, the second one with
 *     both status- and novelty counter.
 * ---
 */
function video_object()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    /*
     * Basic Construction
     */

    $primary_id = "Mountains through the ages - the formation of giants";
    $secondary_id = $f->image()->responsive("assets/ui-examples/images/Image/mountains.jpg", "Some mountains in the dusk");

    // creating the entity object now so it can be filled in the logic section
    $entity = $f->entity()->standard(
        $primary_id,
        $secondary_id
    );

    /*
     * Priority Areas
     */

    $glyph_calendar = $f->symbol()->glyph()->calendar()->withLabel("Published on");
    $glyph_user = $f->symbol()->glyph()->user()->withLabel("Created by");

    $featured_properties = $f->listing()->property()
        ->withProperty($glyph_calendar, '24.01.2025')
        ->withProperty($glyph_user, 'BBC England, Co-Production: ARD/ZDF, Canal Plus')
    ;

    $entity = $entity
        ->withFeaturedProperties($featured_properties)
    ;

    /*
     * Dropdown Actions
     */

    $managing_actions = [
        $f->button()->shy("Copy", "https://www.ilias.de"),
        $f->button()->shy("Delete", "https://www.github.com")
    ];
    $entity = $entity->withManagingActions(...$managing_actions);

    /*
     * Generating Action Buttons from Workflow
     */

    $workflow_factory = $f->listing()->workflow();
    $dummy_step = $workflow_factory->step('', '');

    // Creating Workflow Steps
    $steps = [
        $workflow_factory->step("Upload video file", "Upload an .mp4 file or start a recording.", "#")
            ->withAvailability($dummy_step::NOT_ANYMORE)->withStatus($dummy_step::SUCCESSFULLY),
        $workflow_factory->step("Cut video", "Trim or remove parts of the video.", "#")
            ->withAvailability($dummy_step::AVAILABLE)->withStatus($dummy_step::NOT_STARTED),
        $workflow_factory->step("Add subtitles", "You must upload or generate subtitles for every video.", "#")
            ->withAvailability($dummy_step::AVAILABLE)->withStatus($dummy_step::NOT_STARTED),
        $workflow_factory->step("Publish", "Set who can see this video.", "#")
            ->withAvailability($dummy_step::NOT_AVAILABLE)->withStatus($dummy_step::NOT_AVAILABLE),
    ];

    $video_workflow = $workflow_factory->linear("Video Curation", $steps);

    $entity = $entity->withWorkflow($video_workflow);

    /*
     * All Other Semantic Groups
     */

    $glyph_time = $f->symbol()->glyph()->time()->withLabel("Duration");

    $main_details_01 = $f->listing()->property()
        ->withProperty($glyph_time, '45:00')
    ;
    $main_details_02 = $f->listing()->property()
        ->withProperty('Description', "A fascinating look on the forces of nature that are able to move unimaginable tons of rocks. Find out how seemingly immovable landscape has transformed drastically through the incredible forces set free by earthquakes, vulcanos and water. This award-winning documentary traces the movement of the world's greatest mountain ranges throughout millions of years.", false)
    ;

    $entity = $entity
        ->withMainDetails($main_details_01, $main_details_02)
    ;

    return $renderer->render([
        $entity,
        $f->legacy("<br/><br/><p>The buttons shown in the entity are generated from the following workflow:</p><br/>"),
        $video_workflow
    ]);
}
