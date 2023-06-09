<?php declare(strict_types = 1);

namespace OriNette\ObjectMapper\DI;

use OriNette\DI\Services\ServiceManager;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\ObjectMapper\Processing\DependencyInjector;
use Orisai\ObjectMapper\Processing\DependencyInjectorManager;
use function get_class;

final class LazyDependencyInjectorManager extends ServiceManager implements DependencyInjectorManager
{

	public function get(string $injector): DependencyInjector
	{
		$instance = $this->getTypedServiceOrThrow($injector, DependencyInjector::class);

		$instanceClass = get_class($instance);
		if ($instanceClass !== $injector) {
			$serviceName = $this->getServiceName($injector);

			throw InvalidArgument::create()
				->withMessage(
					"Service '$serviceName' should be exactly the same as '$injector', '$instanceClass' given.",
				);
		}

		return $instance;
	}

}
