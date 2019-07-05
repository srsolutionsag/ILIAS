<?php

namespace ILIAS\Changelog\Membership;


use ILIAS\Changelog\Event;
use ILIAS\Changelog\EventHandler;

/**
 * Class MembershipEventHandler
 * @package ILIAS\Changelog\Membership
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class MembershipEventHandler implements EventHandler {

	/**
	 * @var MembershipRepository
	 */
	protected $repository;

	/**
	 * MembershipEventHandler constructor.
	 * @param MembershipRepository $repository
	 */
	public function __construct(MembershipRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * @param Event $changelogEvent
	 */
	abstract public function handle(Event $changelogEvent): void;

}