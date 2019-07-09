<?php

namespace ILIAS\AssessmentQuestion\Authoring\DomainModel\Question\Command;

use ILIAS\AssessmentQuestion\Authoring\Infrastructure\Persistence\QuestionRepository;
use ILIAS\Messaging\Contract\Command\Command;
use ILIAS\Messaging\Contract\Command\CommandHandler;

/**
 * Class CreateQuestionHandler
 *
 * @package ILIAS\AssessmentQuestion\Authoring\DomainModel\Question\Command
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  Adrian Lüthi <al@studer-raimann.ch>
 */
class SaveQuestionCommandHandler implements CommandHandler {

	/**
	 * @param SaveQuestionCommand $command
	 */
	public function handle(Command $command) {
		QuestionRepository::getInstance()->save($command->GetQuestion());
	}
}