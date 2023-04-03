<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Unit\DI;

use OriNette\DI\Boot\ManualConfigurator;
use OriNette\ObjectMapper\DI\LazyRuleManager;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\ObjectMapper\Rules\MixedRule;
use PHPUnit\Framework\TestCase;
use Tests\OriNette\ObjectMapper\Doubles\ExtendingTestRule;
use Tests\OriNette\ObjectMapper\Doubles\TestRule;
use function dirname;
use function mkdir;
use const PHP_VERSION_ID;

final class RuleManagerTest extends TestCase
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

	public function testRules(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/RuleManager.neon');

		$container = $configurator->createContainer();
		$manager = $container->getByType(LazyRuleManager::class);

		// Default
		self::assertInstanceOf(MixedRule::class, $manager->getRule(MixedRule::class));

		// Lazy
		self::assertFalse($container->isCreated('rule.a'));
		self::assertInstanceOf(TestRule::class, $manager->getRule(TestRule::class));
		self::assertTrue($container->isCreated('rule.a'));

		self::assertFalse($container->isCreated('rule.b'));
		self::assertInstanceOf(ExtendingTestRule::class, $manager->getRule(ExtendingTestRule::class));
		self::assertTrue($container->isCreated('rule.b'));

		// Same instance
		self::assertSame($manager->getRule(MixedRule::class), $manager->getRule(MixedRule::class));
		self::assertSame($manager->getRule(TestRule::class), $manager->getRule(TestRule::class));
	}

	public function testNonExistent(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/RuleManager.broken.neon');

		$container = $configurator->createContainer();
		$manager = $container->getByType(LazyRuleManager::class);

		$this->expectException(InvalidState::class);
		$this->expectExceptionMessage("Rule 'Tests\OriNette\ObjectMapper\Doubles\ExtendingTestRule' does not exist.");

		$manager->getRule(ExtendingTestRule::class);
	}

	public function testWrongType(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/RuleManager.broken.neon');

		$container = $configurator->createContainer();
		$manager = $container->getByType(LazyRuleManager::class);

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage(
			"Service 'rule.a' should be exactly the same as 'Tests\OriNette\ObjectMapper\Doubles\TestRule', " .
			"'Tests\OriNette\ObjectMapper\Doubles\ExtendingTestRule' given.",
		);

		$manager->getRule(TestRule::class);
	}

}
