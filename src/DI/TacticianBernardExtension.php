<?php

namespace Cellar\Tactician\Bernard\DI;

use Bernard\Router;
use League\Tactician\Bernard\Receiver\SameBusReceiver;
use Nette\DI\CompilerExtension;

class TacticianBernardExtension extends CompilerExtension
{
	const TAG_RECEIVER = 'tactician.bernard.receiver';

	/** @var array */
	private $defaultConfiguration = [
		'router' => null,
		'receivers' => [
			'default' => null,
		],
		'defaultReceiver' => SameBusReceiver::class
	];

	public function beforeCompile()
	{
		$this->resolveMessageRouter();
		$this->resolveMessageReceivers();

		$config = $this->getConfig($this->defaultConfiguration);

		/** @var QueableCommandProviderInterface[] $queableCommandProviders */
		$queableCommandProviders = $this->compiler->getExtensions(QueableCommandProviderInterface::class);

		foreach ($queableCommandProviders as $commandProvider) {
			foreach ($commandProvider->getQueableCommands() as $queableCommand) {
				$receiver = isset($queableCommand['receiver']) ? $queableCommand['receiver'] : 'default';
				$command = $queableCommand['command'];

				$config['router']
					->addSetup('add', [$command, $config['receivers'][$receiver]]);
			}
		}
	}

	private function resolveMessageRouter(): void
	{
		$builder = $this->getContainerBuilder();
		$routers = $builder->findByType(Router::class);

		if (count($routers) === 0) {
			throw new \RuntimeException('Missing Bernard\Router service definition');
		}

		$this->defaultConfiguration['router'] = reset($routers);
	}

	private function resolveMessageReceivers(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaultConfiguration);

		foreach ($this->defaultConfiguration['receivers'] as $name => $service) {
			if ($service === null) {
				$builder->addDefinition($this->prefix('receiver.' . $name))
					->setClass($this->defaultConfiguration['defaultReceiver'])
					->setAutowired(false);

				$this->defaultConfiguration['receivers'][$name] = $this->prefix('receiver.' . $name);
			} else {
				$config['receivers'][$name] = substr($service, 1);
			}
		}
	}
}