<?php

namespace ILIAS\Changelog\Events\Membership;


use ILIAS\Changelog\Exception\CourseNotFoundException;
use ILIAS\Changelog\Exception\UserNotFoundException;
use ilObjCourse;
use ilObjUser;

/**
 * Class UnsubscribedFromCourse
 * @package ILIAS\Changelog\Events\Membership
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class UnsubscribedFromCourse extends MembershipEvent {

	const TYPE_ID = 4;

	/**
	 * @var int
	 */
	protected $crs_obj_id;
	/**
	 * @var int
	 */
	protected $unsubscribing_user_id;

	/**
	 * UnsubscribedFromCourse constructor.
	 * @param int $crs_obj_id
	 * @param int $unsubscribing_user_id
	 * @throws CourseNotFoundException
	 * @throws UserNotFoundException
	 */
	public function __construct(int $crs_obj_id, int $unsubscribing_user_id) {
		if (!ilObjCourse::_exists($crs_obj_id)) {
			throw new CourseNotFoundException("couldn't find course with obj_id " . $crs_obj_id);
		}
		if (!ilObjUser::_exists($unsubscribing_user_id)) {
			throw new UserNotFoundException("couldn't find user with id " . $unsubscribing_user_id);
		}
		$this->crs_obj_id = $crs_obj_id;
		$this->unsubscribing_user_id = $unsubscribing_user_id;
	}


	public function getTypeId(): int {
		return self::TYPE_ID;
	}

	/**
	 * @return int
	 */
	public function getCrsObjId(): int {
		return $this->crs_obj_id;
	}

	/**
	 * @return int
	 */
	public function getUnsubscribingUserId(): int {
		return $this->unsubscribing_user_id;
	}
}