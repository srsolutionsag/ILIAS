<?php


namespace ILIAS\Data\Domain;

/**
 * Interface AggregateRepository
 * @package ILIAS\Data\Domain
 */
interface AggregateRepository {

	/**
	 * @param IdentifiesAggregate $aggregate_id
	 * @return AggregateRoot
	 */
	public function findById(IdentifiesAggregate $aggregate_id): AggregateRoot;
}