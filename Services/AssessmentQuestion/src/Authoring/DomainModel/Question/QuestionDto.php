<?php

namespace ILIAS\AssessmentQuestion\Authoring\DomainModel\Question;

use ILIAS\AssessmentQuestion\Authoring\DomainModel\Question\Answer\Option\AnswerOptions;

class QuestionDto {

	/**
	 * @var string
	 */
	private $id;
	/**
	 * @var string
	 */
	private $revision_id;
	/**
	 * @var string
	 */
	private $revision_name = "";
	/**
	 * @var QuestionData
	 */
	private $data;
	/**
	 * @var QuestionPlayConfiguration
	 */
	private $play_configuration;
	/**
	 * @var QuestionLegacyData
	 */
	private $legacy_data;
	/**
	 * @var AnswerOptions
	 */
	private $answer_options;

	/**
	 * @param Question $question
	 *
	 * @return QuestionDto
	 */
	public static function CreateFromQuestion(Question $question) : QuestionDto {
		$dto = new QuestionDto();
		$dto->id = $question->getAggregateId()->getId();

		if ($question->getRevisionId() !== null) {
			$dto->revision_id = $question->getRevisionId()->getKey();
			$dto->revision_name = $question->getRevisionName();
		}

		$dto->data = $question->getData();
		$dto->play_configuration = $question->getPlayConfiguration();
		$dto->answer_options = $question->getAnswerOptions();
		$dto->legacy_data = $question->getLegacyData();
		return $dto;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}


	/**
	 * @param string $id
	 */
	public function setId(string $id) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getRevisionId(): string {
		return $this->revision_id;
	}


	/**
	 * @return string
	 */
	public function getRevisionName(): string {
		return $this->revision_name;
	}

	/**
	 * @return QuestionData
	 */
	public function getData(): ?QuestionData {
		return $this->data;
	}


	/**
	 * @param QuestionData $data
	 */
	public function setData(QuestionData $data): void {
		$this->data = $data;
	}


	/**
	 * @return QuestionLegacyData
	 */
	public function getLegacyData(): ?QuestionLegacyData {
		return $this->legacy_data;
	}


	/**
	 * @param QuestionLegacyData $legacy_data
	 */
	public function setLegacyData(QuestionLegacyData $legacy_data): void {
		$this->legacy_data = $legacy_data;
	}

	/**
	 * @return QuestionPlayConfiguration
	 */
	public function getPlayConfiguration(): ?QuestionPlayConfiguration {
		return $this->play_configuration;
	}


	/**
	 * @param QuestionPlayConfiguration $play_configuration
	 */
	public function setPlayConfiguration(QuestionPlayConfiguration $play_configuration): void {
		$this->play_configuration = $play_configuration;
	}


	/**
	 * @return AnswerOptions
	 */
	public function getAnswerOptions(): AnswerOptions {
		return $this->answer_options;
	}


	/**
	 * @param AnswerOptions $answer_options
	 */
	public function setAnswerOptions(AnswerOptions $answer_options): void {
		$this->answer_options = $answer_options;
	}
}