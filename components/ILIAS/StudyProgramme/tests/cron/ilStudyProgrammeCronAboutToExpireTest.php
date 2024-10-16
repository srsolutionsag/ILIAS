<?php

declare(strict_types=1);

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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ilPrgUserNotRestartedCronJobMock extends ilPrgUserNotRestartedCronJob
{
    public array $logs = [];

    public function __construct(
        ilPRGAssignmentDBRepository $repo,
        ilPrgCronJobAdapter $adapter
    ) {
        $this->assignment_repo = $repo;
        $this->adapter = $adapter;
    }
    protected function getNow(): DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Ymd', '20221224');
    }

    protected function log(string $msg): void
    {
        $this->logs[] = $msg;
    }
}

class ilStudyProgrammeCronAboutToExpireTest extends TestCase
{
    protected ilPrgUserNotRestartedCronJobMock $job;
    protected ilStudyProgrammeSettingsDBRepository $settings_repo;
    protected ilPRGAssignmentDBRepository $assignment_repo;
    protected ProgrammeEventsMock $events;
    protected ilPrgNotRestarted $adapter;
    protected ilPrgNotRestarted $real_adapter;

    protected function setUp(): void
    {
        $this->events = new ProgrammeEventsMock();
        $this->settings_repo = $this->getMockBuilder(ilStudyProgrammeSettingsDBRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProgrammeIdsWithMailsForExpiringValidity'])
            ->getMock();

        $this->adapter = $this->getMockBuilder(ilPrgNotRestarted::class)
            ->setConstructorArgs([$this->settings_repo, $this->events])
            ->getMock();

        $this->real_adapter = new ilPrgNotRestarted($this->settings_repo, $this->events);

        $this->assignment_repo = $this->getMockBuilder(ilPRGAssignmentDBRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAboutToExpire', 'storeExpiryInfoSentFor'])
            ->getMock();

        $this->job = new ilPrgUserNotRestartedCronJobMock($this->assignment_repo, $this->adapter);
    }

    public function testAboutToExpireForNoRelevantProgrammes(): void
    {
        $this->adapter
            ->expects($this->once())
            ->method('getRelevantProgrammeIds')
            ->willReturn([]);
        $this->assignment_repo
            ->expects($this->never())
            ->method('getAboutToExpire');
        $this->assignment_repo
            ->expects($this->never())
            ->method('storeExpiryInfoSentFor');
        $this->adapter
            ->expects($this->never())
            ->method('actOnSingleAssignment');
        $this->job->run();
    }

    public function testAboutToExpireForRelevantProgrammes(): void
    {
        $pgs1 = (new ilPRGProgress(11, ilPRGProgress::STATUS_COMPLETED));
        $ass1 = (new ilPRGAssignment(42, 7))
            ->withProgressTree($pgs1);
        $ass2 = (new ilPRGAssignment(43, 8))
            ->withProgressTree($pgs1);

        $this->adapter
            ->expects($this->once())
            ->method('getRelevantProgrammeIds')
            ->willReturn([
                1 => 3
            ]);
        $this->assignment_repo
            ->expects($this->once())
            ->method('getAboutToExpire')
            ->willReturn([$ass1, $ass2]);

        $this->assignment_repo
            ->expects($this->exactly(2))
            ->method('storeExpiryInfoSentFor');

        $this->adapter
            ->expects($this->exactly(2))
            ->method('actOnSingleAssignment');

        $this->job->run();
    }

    public function testAboutToExpireEvents(): void
    {
        $pgs1 = (new ilPRGProgress(11, ilPRGProgress::STATUS_COMPLETED));
        $ass1 = (new ilPRGAssignment(42, 7))->withProgressTree($pgs1);
        $ass2 = (new ilPRGAssignment(43, 8))->withProgressTree($pgs1);

        $this->settings_repo
            ->expects($this->once())
            ->method('getProgrammeIdsWithMailsForExpiringValidity')
            ->willReturn([
                42 => 3,
                43 => 3
            ]);

        $this->assignment_repo
            ->expects($this->once())
            ->method('getAboutToExpire')
            ->willReturn([$ass1, $ass2]);

        $job = new ilPrgUserNotRestartedCronJobMock($this->assignment_repo, $this->real_adapter);
        $job->run();

        $this->assertEquals(2, count($job->logs));
        $expected_events = [
            ['informUserToRestart', ["ass_id" => 42, 'root_prg_id' => 11]],
            ['informUserToRestart', ["ass_id" => 43, 'root_prg_id' => 11]]
        ];
        $this->assertEquals($expected_events, $this->events->raised);
    }
}
