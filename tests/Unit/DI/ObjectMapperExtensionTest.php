<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Unit\DI;

use OriNette\DI\Boot\ManualConfigurator;
use OriNette\ObjectMapper\Cache\NetteMetaCache;
use OriNette\ObjectMapper\DI\LazyMetaSourceManager;
use OriNette\ObjectMapper\DI\LazyObjectCreator;
use OriNette\ObjectMapper\DI\LazyRuleManager;
use Orisai\ObjectMapper\Attributes\AnnotationsMetaSource;
use Orisai\ObjectMapper\Attributes\AttributesMetaSource;
use Orisai\ObjectMapper\Meta\MetaCache;
use Orisai\ObjectMapper\Meta\MetaLoader;
use Orisai\ObjectMapper\Meta\MetaResolverFactory;
use Orisai\ObjectMapper\Meta\MetaSource;
use Orisai\ObjectMapper\Meta\MetaSourceManager;
use Orisai\ObjectMapper\Processing\DefaultProcessor;
use Orisai\ObjectMapper\Processing\ObjectCreator;
use Orisai\ObjectMapper\Processing\Processor;
use Orisai\ObjectMapper\Rules\RuleManager;
use Orisai\ReflectionMeta\Reader\AnnotationsMetaReader;
use Orisai\ReflectionMeta\Reader\AttributesMetaReader;
use Orisai\ReflectionMeta\Reader\MetaReader;
use Orisai\Utils\Dependencies\DependenciesTester;
use PHPUnit\Framework\TestCase;
use Tests\OriNette\ObjectMapper\Doubles\ExtendingTestRule;
use Tests\OriNette\ObjectMapper\Doubles\TestRule;
use function dirname;
use function mkdir;
use const PHP_VERSION_ID;

/**
 * @runTestsInSeparateProcesses
 */
final class ObjectMapperExtensionTest extends TestCase
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

	public function testMinimal(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/ObjectMapperExtension.minimal.neon');

		$container = $configurator->createContainer();

		$processor = $container->getService('orisai.objectMapper.processor');
		self::assertInstanceOf(DefaultProcessor::class, $processor);
		self::assertSame($processor, $container->getByType(Processor::class));

		self::assertTrue($container->isCreated('orisai.objectMapper.metaSourceManager'));
		$metaSourceManager = $container->getService('orisai.objectMapper.metaSourceManager');
		self::assertInstanceOf(LazyMetaSourceManager::class, $metaSourceManager);
		self::assertNull($container->getByType(MetaSourceManager::class, false));

		self::assertTrue($container->isCreated('orisai.objectMapper.metaCache'));
		$metaCache = $container->getService('orisai.objectMapper.metaCache');
		self::assertInstanceOf(NetteMetaCache::class, $metaCache);
		self::assertNull($container->getByType(MetaCache::class, false));

		self::assertTrue($container->isCreated('orisai.objectMapper.metaResolver.factory'));
		$metaResolverFactory = $container->getService('orisai.objectMapper.metaResolver.factory');
		self::assertInstanceOf(MetaResolverFactory::class, $metaResolverFactory);
		self::assertNull($container->getByType(MetaResolverFactory::class, false));

		self::assertTrue($container->isCreated('orisai.objectMapper.metaLoader'));
		$metaResolverFactory = $container->getService('orisai.objectMapper.metaLoader');
		self::assertInstanceOf(MetaLoader::class, $metaResolverFactory);
		self::assertSame($metaResolverFactory, $container->getByType(MetaLoader::class));

		self::assertTrue($container->isCreated('orisai.objectMapper.ruleManager'));
		$ruleManager = $container->getService('orisai.objectMapper.ruleManager');
		self::assertInstanceOf(LazyRuleManager::class, $ruleManager);
		self::assertNull($container->getByType(RuleManager::class, false));

		self::assertTrue($container->isCreated('orisai.objectMapper.objectCreator'));
		$objectCreator = $container->getService('orisai.objectMapper.objectCreator');
		self::assertInstanceOf(LazyObjectCreator::class, $objectCreator);
		self::assertNull($container->getByType(ObjectCreator::class, false));
	}

	public function testAnnotationMetaSource(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/ObjectMapperExtension.minimal.neon');

		$container = $configurator->createContainer();

		$container->getService('orisai.objectMapper.metaSourceManager');

		$reader = $container->getService('orisai.objectMapper.metaReader.annotations');
		self::assertInstanceOf(AnnotationsMetaReader::class, $reader);
		self::assertNull($container->getByType(MetaReader::class, false));

		$source = $container->getService('orisai.objectMapper.metaSource.annotations');
		self::assertInstanceOf(AnnotationsMetaSource::class, $source);
		self::assertNull($container->getByType(MetaSource::class, false));

		$manager = $container->getService('orisai.objectMapper.metaSourceManager');
		self::assertInstanceOf(LazyMetaSourceManager::class, $manager);
		self::assertContains($source, $manager->getAll());
	}

	public function testAnnotationMetaSourceDisabled(): void
	{
		DependenciesTester::addIgnoredPackages(['doctrine/annotations']);

		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/ObjectMapperExtension.minimal.neon');

		$container = $configurator->createContainer();

		self::assertFalse($container->hasService('orisai.objectMapper.metaSource.annotations'));
		self::assertFalse($container->hasService('orisai.objectMapper.metaReader.annotations'));
	}

	public function testAttributeMetaSource(): void
	{
		if (!AttributesMetaReader::canBeConstructed()) {
			self::markTestSkipped('Requires PHP 8.0+');
		}

		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/ObjectMapperExtension.minimal.neon');

		$container = $configurator->createContainer();

		$reader = $container->getService('orisai.objectMapper.metaReader.attributes');
		self::assertInstanceOf(AttributesMetaReader::class, $reader);
		self::assertNull($container->getByType(MetaReader::class, false));

		$source = $container->getService('orisai.objectMapper.metaSource.attributes');
		self::assertInstanceOf(AttributesMetaSource::class, $source);
		self::assertNull($container->getByType(MetaSource::class, false));

		$manager = $container->getService('orisai.objectMapper.metaSourceManager');
		self::assertInstanceOf(LazyMetaSourceManager::class, $manager);
		self::assertContains($source, $manager->getAll());
	}

	public function testCustomRules(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/ObjectMapperExtension.rules.neon');

		$container = $configurator->createContainer();

		$ruleManager = $container->getService('orisai.objectMapper.ruleManager');
		self::assertInstanceOf(LazyRuleManager::class, $ruleManager);

		self::assertInstanceOf(TestRule::class, $ruleManager->getRule(TestRule::class));
		self::assertInstanceOf(ExtendingTestRule::class, $ruleManager->getRule(ExtendingTestRule::class));
	}

}
