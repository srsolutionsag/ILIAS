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
 */

declare(strict_types=1);

use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\SRAG\SpacifyStringJob;

/**
 * @author       Thibeau Fuhrer <thibeau@sr.solutions>
 * @noinspection AutoloadingIssuesInspection
 */
class ilSRAGControllerGUI implements ilCtrlBaseClassInterface
{
    public function executeCommand(): void
    {
        global $DIC;

        switch ($DIC->ctrl()->getCmd()) {
            case 'start_bg_task':
                $this->startBackgroundTask();
                break;
            default:
                $this->index();
        }

        $DIC->ui()->mainTemplate()->printToStdout();
    }

    protected function index(): void
    {
        global $DIC;

        foreach ($DIC->backgroundTasks()->persistence()->getBucketIdsOfUser($DIC->user()->getId()) as $bucket_id) {
            $bucket = $DIC->backgroundTasks()->persistence()->loadBucket($bucket_id);
            if (100 <= $bucket->getOverallPercentage()) {
                $DIC->backgroundTasks()->taskManager()->quitBucket($bucket);
            }
        }

        $DIC->ui()->mainTemplate()->setContent(
            $DIC->ui()->renderer()->render([
                $DIC->ui()->factory()->button()->standard(
                    'start background task',
                    $this->getStartBackgroundTaskAction(),
                ),
            ]),
        );
    }

    protected function startBackgroundTask(): void
    {
        global $DIC;

        $bucket = new BasicBucket();
        $bucket->setUserId($DIC->user()->getId());
        $bucket->setTitle('Spacification of some string.');
        $bucket->setDescription('Will add some spaces to the predefined string.');

        $input = 'Hello World!!!';

        $job_1 = $DIC->backgroundTasks()->taskFactory()->createTask(SpacifyStringJob::class, [$input]);
        $job_2 = $DIC->backgroundTasks()->taskFactory()->createTask(SpacifyStringJob::class, [$input]);
        $job_3 = $DIC->backgroundTasks()->taskFactory()->createTask(SpacifyStringJob::class, [$input]);

        $bucket->setTask($job_1);
        $bucket->setTask($job_2);
        $bucket->setTask($job_3);

        $DIC->backgroundTasks()->taskManager()->run($bucket);

        $DIC->ctrl()->redirectByClass([self::class], 'index');
    }

    protected function getStartBackgroundTaskAction(): string
    {
        global $DIC;

        return $DIC->ctrl()->getLinkTargetByClass([self::class], 'start_bg_task');
    }
}
