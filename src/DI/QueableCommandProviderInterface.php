<?php

namespace Cellar\Tactician\Bernard\DI;

interface QueableCommandProviderInterface
{
	/**
	 * @return string[]
	 */
	public function getQueableCommands(): array;
}