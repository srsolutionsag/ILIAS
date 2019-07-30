<?php

namespace ILIAS\AssessmentQuestion\Authoring\DomainModel\Question;

use JsonSerializable;

/**
 * Class QuestionPlayConfiguration
 *
 * @package ILIAS\AssessmentQuestion\Authoring\DomainModel\Question
 *
 * @author  Adrian Lüthi <al@studer-raimann.ch>
 */
class QuestionLegacyData implements JsonSerializable {
	/**
	 * @var int;
	 */
	private $container_obj_id;
	/**
	 * @var int
	 */
	private $answer_type_id;

	/**
	 * QuestionLegacyData constructor.
	 *
	 * @param int $answer_type_id
	 * @param int $container_obj_id
	 */
	public function __construct(int $answer_type_id, int $container_obj_id) {
		$this->answer_type_id = $answer_type_id;
		$this->container_obj_id = $container_obj_id;
	}


	public static function getQuestionTypes() : array {
		$question_types = [];
		$question_types[0] = 'GenericQuestion ';
		$question_types[1] = 'Single Choice ';
		$question_types[2] = 'Multiple Choice ';
		$question_types[3] = 'Cloze Test ';
		$question_types[4] = 'Matching Question ';
		$question_types[5] = 'Ordering Question ';
		$question_types[6] = 'Imagemap Question ';
		$question_types[7] = 'Java Applet ';
		$question_types[8] = 'Text Question ';
		$question_types[9] = 'Numeric ';
		$question_types[10] = 'Text Subset ';
		$question_types[11] = 'Flash Question ';
		$question_types[12] = 'Ordering Horizontal ';
		$question_types[13] = 'File Upload ';
		$question_types[14] = 'Error Text ';
		$question_types[15] = 'Formula Question ';
		$question_types[16] = 'Kprim Choice ';
		$question_types[17] = 'Long Menu ';
		return $question_types;
	}


	/**
	 * @return int
	 */
	public function getContainerObjId(): int {
		return $this->container_obj_id;
	}

	/**
	 * @return int
	 */
	public function getAnswerTypeId(): int {
		return $this->answer_type_id;
	}


	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		return get_object_vars($this);
	}
}