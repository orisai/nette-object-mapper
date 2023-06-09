<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Doubles\Dependencies;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;

final class InjectorAddingExtension extends CompilerExtension
{

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('injector'))
			->setFactory(DependentBaseVoInjector::class, [
				new Statement('stdClass'),
			]);
	}

}
