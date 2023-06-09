<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Unit\DI;

use OriNette\DI\Boot\ManualConfigurator;
use OriNette\ObjectMapper\DI\LazyDependencyInjectorManager;
use Orisai\Exceptions\Logic\InvalidArgument;
use PHPUnit\Framework\TestCase;
use Tests\OriNette\ObjectMapper\Doubles\Dependencies\DependentBaseVoInjector;
use Tests\OriNette\ObjectMapper\Doubles\Dependencies\DependentChildVoInjector1;
use function assert;
use function dirname;
use function mkdir;
use const PHP_VERSION_ID;

final class LazyDependencyInjectorManagerTest extends TestCase
{

	private string $rootDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->rootDir = dirname(__DIR__, 3);
		if (PHP_VERSION_ID < 8_01_00) {
			@mkdir("$this->rootDir/var/build");
		}
	}

	public function testInjectors(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/DependencyInjectorManager.neon');

		$container = $configurator->createContainer();
		$manager = $container->getByType(LazyDependencyInjectorManager::class);
		assert($manager !== null);

		// Lazy
		self::assertFalse($container->isCreated('injector.a'));
		self::assertInstanceOf(DependentBaseVoInjector::class, $manager->get(DependentBaseVoInjector::class));
		self::assertTrue($container->isCreated('injector.a'));

		self::assertFalse($container->isCreated('injector.b'));
		self::assertInstanceOf(DependentChildVoInjector1::class, $manager->get(DependentChildVoInjector1::class));
		self::assertTrue($container->isCreated('injector.b'));

		// Same instance
		self::assertSame($manager->get(DependentBaseVoInjector::class), $manager->get(DependentBaseVoInjector::class));
		self::assertSame(
			$manager->get(DependentChildVoInjector1::class),
			$manager->get(DependentChildVoInjector1::class),
		);
	}

	public function testNonExistent(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/DependencyInjectorManager.broken.neon');

		$container = $configurator->createContainer();
		$manager = $container->getByType(LazyDependencyInjectorManager::class);
		assert($manager !== null);

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage(
			<<<'MSG'
Context: Trying to get service by key
         'Tests\OriNette\ObjectMapper\Doubles\Dependencies\DependentBaseVoInjector'
         from OriNette\ObjectMapper\DI\LazyDependencyInjectorManager.
Problem: No service is registered under that key but service of type
         Orisai\ObjectMapper\Processing\DependencyInjector is required.
Solution: Add service with key
          'Tests\OriNette\ObjectMapper\Doubles\Dependencies\DependentBaseVoInjector'
          to LazyDependencyInjectorManager.
MSG,
		);

		$manager->get(DependentBaseVoInjector::class);
	}

	public function testWrongType(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/DependencyInjectorManager.broken.neon');

		$container = $configurator->createContainer();
		$manager = $container->getByType(LazyDependencyInjectorManager::class);
		assert($manager !== null);

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage(
			"Service 'injector.a' should be exactly the same as"
			. " 'Tests\OriNette\ObjectMapper\Doubles\Dependencies\DependentChildVoInjector1',"
			. " 'Tests\OriNette\ObjectMapper\Doubles\Dependencies\DependentBaseVoInjector' given.",
		);

		$manager->get(DependentChildVoInjector1::class);
	}

}
