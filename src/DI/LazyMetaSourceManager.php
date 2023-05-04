<?php declare(strict_types = 1);

namespace OriNette\ObjectMapper\DI;

use Nette\DI\Container;
use Orisai\ObjectMapper\Meta\Source\MetaSource;
use Orisai\ObjectMapper\Meta\Source\MetaSourceManager;
use function assert;

final class LazyMetaSourceManager implements MetaSourceManager
{

	private Container $container;

	/** @var array<string> */
	private array $services = [];

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function addLazySource(string $serviceName): void
	{
		$this->services[] = $serviceName;
	}

	public function getAll(): array
	{
		$instances = [];

		foreach ($this->services as $service) {
			$instance = $this->container->getService($service);
			assert($instance instanceof MetaSource);
			$instances[] = $instance;
		}

		return $instances;
	}

}
