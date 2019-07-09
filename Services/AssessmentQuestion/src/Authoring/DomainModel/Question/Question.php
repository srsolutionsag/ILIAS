<?php

namespace ILIAS\AssessmentQuestion\Authoring\DomainModel\Question;

use ILIAS\AssessmentQuestion\Authoring\DomainModel\Question\Event\QuestionRevisionCreatedEvent;
use ILIAS\AssessmentQuestion\Common\DomainModel\Aggregate\DomainObjectId;
use ILIAS\AssessmentQuestion\Authoring\DomainModel\Question\Event\QuestionDataSetEvent;
use ILIAS\AssessmentQuestion\Authoring\DomainModel\Question\Event\QuestionCreatedEvent;
use ILIAS\AssessmentQuestion\Common\DomainModel\Aggregate\AggregateRoot;
use ILIAS\AssessmentQuestion\Common\DomainModel\Aggregate\Event\DomainEvents;
use ILIAS\AssessmentQuestion\Common\IsRevisable;
use ILIAS\AssessmentQuestion\Common\RevisionId;
use ILIAS\AssessmentQuestion\Common\DomainModel\Aggregate\AbstractEventSourcedAggregateRoot;

/**
 * Class Question
 *
 * @package ILIAS\AssessmentQuestion\Authoring\DomainModel\Question
 * @author  Martin Studer <ms@studer-raimann.ch>
 */
class Question extends AbstractEventSourcedAggregateRoot implements IsRevisable {

	/**
	 * @var DomainObjectId
	 */
	private $id;
	/**
	 * @var RevisionId
	 */
	private $revision_id;
	/**
	 * @var string
	 */
	private $revision_name;
	/**
	 * @var int
	 */
	private $creator_id;
	/**
	 * @var QuestionData
	 */
	private $data;
	/**
	 * @var
	 */
	private $possible_answers;

	//TODO Sollten wir nicht darauf achten, dass ein Objekt immer einen korrekten
	//Status hat? So hätten wir Fragen ohne Titel. Titel ist m.E. die Mindestanforderung einer Frage!
	protected function __construct() {
		parent::__construct();
	}


	/**
	 * @param string $title
	 * @param string $description
	 *
	 * @param int    $creator_id
	 *
	 * @return Question
	 */
	public static function createNewQuestion(int $creator_id) {
		$question = new Question();
		$question->ExecuteEvent(new QuestionCreatedEvent(new DomainObjectId(), $creator_id));
		return $question;
	}


	protected function applyQuestionCreatedEvent(QuestionCreatedEvent $event) {
		$this->id = $event->getAggregateId();
		$this->creator_id = $event->getInitiatingUserId();
	}

	protected function applyQuestionDataSetEvent(QuestionDataSetEvent $event) {
		$this->data = $event->getData();
	}

	protected function applyQuestionRevisionCreatedEvent(QuestionRevisionCreatedEvent $event) {
		$this->revision_id = new RevisionId($event->getRevisionKey());
	}

	public function getOnlineState() : bool {
		return $this->online;
	}

	/**
	 * @return QuestionData
	 */
	public function getData(): QuestionData {
		return $this->data;
	}


	/**
	 * @param QuestionData $data
	 * @param int          $creator_id
	 */
	public function setData(QuestionData $data, int $creator_id = 3): void {
		$this->ExecuteEvent(new QuestionDataSetEvent($this->getAggregateId(), $creator_id, $data));
	}


	/**
	 * @return int
	 */
	public function getCreatorId(): int {
		return $this->creator_id;
	}


	/**
	 * @param int $creator_id
	 */
	public function setCreatorId(int $creator_id): void {
		$this->creator_id = $creator_id;
	}


	/**
	 * @return RevisionId revision id of object
	 */
	public function getRevisionId(): ?RevisionId {
		return $this->revision_id;
	}


	/**
	 * @param RevisionId $id
	 *
	 * Revision id is only to be set by the RevisionFactory when generating a
	 * revision or by the persistance layer when loading an object
	 *
	 * @return mixed
	 */
	public function setRevisionId(RevisionId $id) {
		$this->ExecuteEvent(new QuestionRevisionCreatedEvent($this->getAggregateId(), $this->creator_id, $id->GetKey()));
	}

	/**
	 * @return string
	 *
	 * Name of the revision used by the RevisionFactory when generating a revision
	 * Using of Creation Date and or an increasing Number are encouraged
	 *
	 */
	public function getRevisionName(): ?string {
		return time();
	}


	/**
	 * @return array
	 *
	 * Data used for signing the revision, so this method needs to to collect all
	 * Domain specific data of an object and return it as an array
	 */
	public function getRevisionData(): array {
		$data[] = $this->getAggregateId()->getId();
		$data[] = $this->getData()->jsonSerialize();
		return $data;
	}


	public static function reconstitute(DomainEvents $event_history): AggregateRoot {
		$question = new Question();
		foreach ($event_history->getEvents() as $event) {
			$question->applyEvent($event);
		}
		return $question;
	}


	function getAggregateId(): DomainObjectId {
		return $this->id;
	}
}