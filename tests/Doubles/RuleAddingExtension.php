<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Doubles;

use Nette\DI\CompilerExtension;

final class RuleAddingExtension extends CompilerExtension
{

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('rule'))
			->setFactory(TestRule::class);
	}

}
