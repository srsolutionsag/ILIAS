<?php
/* Copyright (c) 2019 - Martin Studer <ms@studer-raimann.ch> - Extended GPL, see LICENSE */

namespace ILIAS\Messaging\Contract\Command;

use DateTime;

abstract class AbstractCommand implements Command {
	/**
	 * @var int
	 */
	protected $creator_id;

	/**
	 * @var DateTime
	 */
	protected $creation_time;
}