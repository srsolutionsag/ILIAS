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

use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use ILIAS\components\OrgUnit\ARHelper\DIC;
use ILIAS\Filesystem\Stream\Streams;

/**
 * Class ilBTControllerGUI
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilBTControllerGUI implements ilCtrlBaseClassInterface
{
    use DIC;

    public const FROM_URL = 'from_url';
    public const OBSERVER_ID = 'observer_id';
    public const SELECTED_OPTION = 'selected_option';
    public const CMD_ABORT = 'abortBucket';
    public const CMD_REMOVE = 'abortBucket';
    public const CMD_USER_INTERACTION = 'userInteraction';
    public const IS_ASYNC = 'bt_task_is_async';
    public const CMD_REFRESH_NOTIFICATION_ITEM = "getAsyncNotificationItemState";
    public const CMD_PROGRESS_BAR_STATE = "getAsyncProgressBarState";

    public function executeCommand(): void
    {
        $cmd = $this->ctrl()->getCmd();
        switch ($cmd) {
            case self::CMD_REFRESH_NOTIFICATION_ITEM:
                $this->getAsyncNotificationItemReplacement();
                break;
            case self::CMD_PROGRESS_BAR_STATE:
                $this->getAsyncProgressBarState();
                break;
            case self::CMD_USER_INTERACTION:
                $this->userInteraction();
                break;
            case self::CMD_ABORT:
            case self::CMD_REMOVE:
                $this->abortBucket();
                break;
            default:
                break;
        }
    }

    protected function userInteraction(): void
    {
        $observer_id = (int) $this->http()->request()->getQueryParams()[self::OBSERVER_ID];
        $selected_option = $this->http()->request()->getQueryParams()[self::SELECTED_OPTION];
        $from_url = $this->getFromURL();

        $observer = $this->dic()->backgroundTasks()->persistence()->loadBucket($observer_id);
        if ($observer->getUserId() !== $this->user()->getId()) {
            return;
        }
        $option = new UserInteractionOption("", $selected_option);
        $this->dic()->backgroundTasks()->taskManager()->continueTask($observer, $option);
        if ($this->http()->request()->getQueryParams()[self::IS_ASYNC] === "true") {
            $this->http()->close();
        }
        $this->ctrl()->redirectToURL($from_url);
    }

    protected function abortBucket(): void
    {
        $observer_id = (int) $this->http()->request()->getQueryParams()[self::OBSERVER_ID];
        $from_url = $this->getFromURL();

        $bucket = $this->dic()->backgroundTasks()->persistence()->loadBucket($observer_id);

        $this->dic()->backgroundTasks()->taskManager()->quitBucket($bucket);
        if ($this->http()->request()->getQueryParams()[self::IS_ASYNC] === "true") {
            exit;
        }
        $this->ctrl()->redirectToURL($from_url);
    }

    /**
     * Updates the @see ILIAS\UI\Component\Progress\Bar of the requested observer (id)
     * on the client asynchronously.
     */
    protected function getAsyncProgressBarState(): void
    {
        $observer_id = (int) $this->http()->request()->getQueryParams()[self::OBSERVER_ID];
        $bucket = $this->dic()->backgroundTasks()->persistence()->loadBucket($observer_id);

        $item_source = new ilBTPopOverGUI($this->dic());
        $this->dic()->language()->loadLanguageModule('background_tasks');

        $progress_bar_state = $item_source->getProgressBarState($bucket);
        $html = $this->ui()->renderer()->renderAsync($progress_bar_state);

        $this->sendHtmlResponse($html);
    }

    /**
     * Updates the @see ILIAS\UI\Component\Item\Notification SURROUNDINGS (not content)
     * of the requested observer (id) on the client asynchronously.
     */
    protected function getAsyncNotificationItemReplacement(): void
    {
        $observer_id = (int) $this->http()->request()->getQueryParams()[self::OBSERVER_ID];
        $bucket = $this->dic()->backgroundTasks()->persistence()->loadBucket($observer_id);

        $item_source = new ilBTPopOverGUI($this->dic());
        $this->dic()->language()->loadLanguageModule('background_tasks');

        $replacement_notification_item = $item_source->getItemForObserver($bucket);
        $html = $this->ui()->renderer()->renderAsync($replacement_notification_item);

        $this->sendHtmlResponse($html);
    }

    protected function sendHtmlResponse(string $html): void
    {
        $this->http()->saveResponse(
            $this->http()->response()
                 ->withHeader('Content-Type', 'text/html; charset=utf-8')
                 ->withBody(Streams::ofString($html))
        );
        $this->http()->sendResponse();
        $this->http()->close();
    }

    protected function getFromURL(): string
    {
        return self::unhash($this->http()->request()->getQueryParams()[self::FROM_URL]);
    }

    /**
     * @param $url
     */
    public static function hash($url): string
    {
        return base64_encode((string) $url);
    }

    /**
     * @param $url
     */
    public static function unhash($url): string
    {
        return base64_decode((string) $url);
    }
}
