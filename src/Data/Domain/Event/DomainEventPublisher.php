<?php

namespace ILIAS\Data\Domain;


/**
 * Class DomainEventPublisher
 * @package ILIAS\Data\Domain
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class DomainEventPublisher {

	/**
	 * @var array
	 */
	private $subscribers;
	/**
	 * @var DomainEventPublisher
	 */
	private static $instance = null;

	/**
	 * @return DomainEventPublisher
	 */
	public static function getInstance(): DomainEventPublisher {
		if (!isset(static::$instance)) {
			static::$instance = new DomainEventPublisher();
		}
		return static::$instance;
	}

	/**
	 * DomainEventPublisher constructor.
	 */
	private function __construct() {
		$this->subscribers = [];
	}

	/**
	 * @param DomainEventSubscriber $aDomainEventSubscriber
	 */
	public function subscribe(DomainEventSubscriber $aDomainEventSubscriber) {
		$this->subscribers[] = $aDomainEventSubscriber;
	}

	/**
	 * @param DomainEvent $anEvent
	 */
	public function publish(DomainEvent $anEvent) {
		foreach ($this->subscribers as $aSubscriber) {
			if ($aSubscriber->isSubscribedTo($anEvent)) {
				$aSubscriber->handle($anEvent);
			}
		}
	}
}