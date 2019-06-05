<?php

namespace ILIAS\Messaging\Adapter\SimpleBus\Command;

use ILIAS\Messaging\Contract\Command\Command;

use ILIAS\Messaging\Contract\Command\CommandBus as CommandBusContract;
use ILIAS\Messaging\Contract\Command\CommandHandlerMiddleware as CommandHandlerMiddlewareContract;

class CommandBus implements CommandBusContract {

	/**
	 * @var CommandBus
	 */
	private $command_bus_adapter;


	/**
	 * CommandBusAdapter constructor.
	 *
	 * @param CommandHandlerMiddlewareContract[] $middlewares
	 */
	public function __construct(array $middlewares) {
		$this->command_bus_adapter = new CommandBusWithMiddlewareSupportAdapter();

		foreach ($middlewares as $middleware) {
			/**
			 * @var CommandHandlerMiddlewareContract $middleware
			 */
			$this->appendMiddleware($middleware);
		}
	}


	/**
	 * Appends new middleware for this message bus.
	 * Should only be used at configuration time.
	 *
	 * @param CommandHandlerMiddlewareContract $middleware
	 *
	 * @return void
	 */
	public function appendMiddleware($middleware): void {
		$this->command_bus_adapter->appendMiddleware($middleware);
	}


	public function handle(Command $command): void {
		$this->command_bus_adapter->handle($command);
	}
}