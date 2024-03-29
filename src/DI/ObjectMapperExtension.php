<?php declare(strict_types = 1);

namespace OriNette\ObjectMapper\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator\Literal;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use OriNette\DI\Definitions\DefinitionsLoader;
use OriNette\ObjectMapper\Cache\NetteMetaCache;
use Orisai\ObjectMapper\Meta\Cache\MetaCache;
use Orisai\ObjectMapper\Meta\MetaLoader;
use Orisai\ObjectMapper\Meta\MetaResolverFactory;
use Orisai\ObjectMapper\Meta\Source\AnnotationsMetaSource;
use Orisai\ObjectMapper\Meta\Source\AttributesMetaSource;
use Orisai\ObjectMapper\Meta\Source\MetaSourceManager;
use Orisai\ObjectMapper\Processing\DefaultProcessor;
use Orisai\ObjectMapper\Processing\ObjectCreator;
use Orisai\ObjectMapper\Processing\Processor;
use Orisai\ObjectMapper\Rules\RuleManager;
use Orisai\ReflectionMeta\Reader\AnnotationsMetaReader;
use Orisai\ReflectionMeta\Reader\AttributesMetaReader;
use stdClass;
use function assert;
use function is_string;

/**
 * @property-read stdClass $config
 */
final class ObjectMapperExtension extends CompilerExtension
{

	private ServiceDefinition $ruleManagerDefinition;

	/** @var list<Definition|Reference> */
	private array $ruleDefinitions;

	private ServiceDefinition $dependencyInjectorManagerDefinition;

	/** @var list<Definition|Reference> */
	private array $injectorDefinitions = [];

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'debug' => Expect::bool(false),
			'dependencyInjectors' => Expect::listOf(
				DefinitionsLoader::schema(),
			),
			'rules' => Expect::listOf(
				DefinitionsLoader::schema(),
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$loader = new DefinitionsLoader($this->compiler);

		$metaCacheDefinition = $this->registerMetaCache($builder, $config->debug);
		$sourceManagerDefinition = $this->registerMetaSourceManager($builder);
		$this->ruleManagerDefinition = $ruleManagerDefinition = $this->registerRuleManager($builder);
		$this->ruleDefinitions = $this->registerRules($config, $loader);
		$this->dependencyInjectorManagerDefinition = $dependencyInjectorManagerDefinition
			= $this->registerDependencyInjectorManager($builder);
		$this->injectorDefinitions = $this->registerInjectors($config, $loader);
		$objectCreatorDefinition = $this->registerObjectCreator($builder, $dependencyInjectorManagerDefinition);
		$resolverFactoryDefinition = $this->registerMetaResolverFactory(
			$builder,
			$ruleManagerDefinition,
			$objectCreatorDefinition,
		);
		$metaLoaderDefinition = $this->registerMetaLoader(
			$builder,
			$metaCacheDefinition,
			$sourceManagerDefinition,
			$resolverFactoryDefinition,
		);
		$this->registerProcessor(
			$builder,
			$metaLoaderDefinition,
			$ruleManagerDefinition,
			$objectCreatorDefinition,
		);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$this->addRulesToRuleManager(
			$builder,
			$this->ruleDefinitions,
			$this->ruleManagerDefinition,
		);
		$this->addInjectorsToDependencyInjectorManager(
			$builder,
			$this->injectorDefinitions,
			$this->dependencyInjectorManagerDefinition,
		);
	}

	private function registerMetaSourceManager(ContainerBuilder $builder): ServiceDefinition
	{
		$definition = $builder->addDefinition($this->prefix('metaSourceManager'))
			->setFactory(LazyMetaSourceManager::class)
			->setType(MetaSourceManager::class)
			->setAutowired(false);

		$this->registerAnnotationsMetaSource($definition, $builder);
		$this->registerAttributesMetaSource($definition, $builder);

		return $definition;
	}

	private function registerAnnotationsMetaSource(
		ServiceDefinition $sourceManagerDefinition,
		ContainerBuilder $builder
	): void
	{
		if (!AnnotationsMetaReader::canBeConstructed()) {
			return;
		}

		$sourceDefinition = $builder->addDefinition($this->prefix('metaSource.annotations'))
			->setFactory(AnnotationsMetaSource::class, [
				$builder->addDefinition($this->prefix('metaReader.annotations'))
					->setFactory(AnnotationsMetaReader::class)
					->setAutowired(false),
			])
			->setAutowired(false);

		$sourceManagerDefinition->addSetup('addLazySource', [
			$sourceDefinition->getName(),
		]);
	}

	private function registerAttributesMetaSource(
		ServiceDefinition $sourceManagerDefinition,
		ContainerBuilder $builder
	): void
	{
		if (!AttributesMetaReader::canBeConstructed()) {
			return;
		}

		$sourceDefinition = $builder->addDefinition($this->prefix('metaSource.attributes'))
			->setFactory(AttributesMetaSource::class, [
				$builder->addDefinition($this->prefix('metaReader.attributes'))
					->setFactory(AttributesMetaReader::class)
					->setAutowired(false),
			])
			->setAutowired(false);

		$sourceManagerDefinition->addSetup('addLazySource', [
			$sourceDefinition->getName(),
		]);
	}

	private function registerMetaCache(ContainerBuilder $builder, bool $debugMode): ServiceDefinition
	{
		return $builder->addDefinition($this->prefix('metaCache'))
			->setFactory(NetteMetaCache::class, [
				'debugMode' => $debugMode,
			])
			->setType(MetaCache::class)
			->setAutowired(false);
	}

	private function registerMetaResolverFactory(
		ContainerBuilder $builder,
		ServiceDefinition $ruleManagerDefinition,
		ServiceDefinition $objectCreatorDefinition
	): ServiceDefinition
	{
		return $builder->addDefinition($this->prefix('metaResolver.factory'))
			->setFactory(MetaResolverFactory::class, [
				'ruleManager' => $ruleManagerDefinition,
				'objectCreator' => $objectCreatorDefinition,
			])
			->setAutowired(false);
	}

	private function registerMetaLoader(
		ContainerBuilder $builder,
		ServiceDefinition $metaCacheDefinition,
		ServiceDefinition $sourceManagerDefinition,
		ServiceDefinition $resolverFactoryDefinition
	): ServiceDefinition
	{
		return $builder->addDefinition($this->prefix('metaLoader'))
			->setFactory(MetaLoader::class, [
				'metaCache' => $metaCacheDefinition,
				'sourceManager' => $sourceManagerDefinition,
				'resolverFactory' => $resolverFactoryDefinition,
			]);
	}

	private function registerRuleManager(ContainerBuilder $builder): ServiceDefinition
	{
		return $builder->addDefinition($this->prefix('ruleManager'))
			->setFactory(LazyRuleManager::class)
			->setType(RuleManager::class)
			->setAutowired(false);
	}

	/**
	 * @return list<Definition|Reference>
	 */
	private function registerRules(stdClass $config, DefinitionsLoader $loader): array
	{
		$definitions = [];
		foreach ($config->rules as $ruleKey => $ruleConfig) {
			$definitions[] = $loader->loadDefinitionFromConfig(
				$ruleConfig,
				$this->prefix("rule.$ruleKey"),
			);
		}

		return $definitions;
	}

	/**
	 * @param list<Definition|Reference> $ruleDefinitions
	 */
	private function addRulesToRuleManager(
		ContainerBuilder $builder,
		array $ruleDefinitions,
		ServiceDefinition $ruleManagerDefinition
	): void
	{
		foreach ($ruleDefinitions as $ruleDefinition) {
			if ($ruleDefinition instanceof Reference) {
				$ruleDefinition = $ruleDefinition->isName()
					? $builder->getDefinition(
						$ruleDefinition->getValue(),
					)
					: $builder->getDefinitionByType(
						$ruleDefinition->getValue(),
					);
			}

			assert($ruleDefinition instanceof ServiceDefinition);

			$ruleName = $ruleDefinition->getFactory()->entity;
			assert(is_string($ruleName));

			$ruleManagerDefinition->addSetup('?->addLazyRule(?, ?)', [
				$ruleManagerDefinition,
				new Literal("\\$ruleName::class"),
				$ruleDefinition->getName(),
			]);
		}
	}

	private function registerDependencyInjectorManager(ContainerBuilder $builder): ServiceDefinition
	{
		return $builder->addDefinition($this->prefix('dependencyInjectorManager'))
			->setFactory(LazyDependencyInjectorManager::class)
			->setAutowired(false);
	}

	/**
	 * @return list<Definition|Reference>
	 */
	private function registerInjectors(stdClass $config, DefinitionsLoader $loader): array
	{
		$definitions = [];
		foreach ($config->dependencyInjectors as $injectorKey => $injectorConfig) {
			$definitions[] = $loader->loadDefinitionFromConfig(
				$injectorConfig,
				$this->prefix("dependencyInjector.$injectorKey"),
			);
		}

		return $definitions;
	}

	/**
	 * @param list<Definition|Reference> $injectorDefinitions
	 */
	private function addInjectorsToDependencyInjectorManager(
		ContainerBuilder $builder,
		array $injectorDefinitions,
		ServiceDefinition $dependencyInjectorManagerDefinition
	): void
	{
		$serviceMap = [];
		foreach ($injectorDefinitions as $injectorDefinition) {
			if ($injectorDefinition instanceof Reference) {
				$injectorDefinition = $injectorDefinition->isName()
					? $builder->getDefinition(
						$injectorDefinition->getValue(),
					)
					: $builder->getDefinitionByType(
						$injectorDefinition->getValue(),
					);
			}

			assert($injectorDefinition instanceof ServiceDefinition);

			$injectorName = $injectorDefinition->getFactory()->entity;
			assert(is_string($injectorName));

			$serviceMap[$injectorName] = $injectorDefinition->getName();
		}

		$dependencyInjectorManagerDefinition->setArguments([
			'serviceMap' => $serviceMap,
		]);
	}

	private function registerObjectCreator(
		ContainerBuilder $builder,
		ServiceDefinition $dependencyInjectorManagerDefinition
	): ServiceDefinition
	{
		return $builder->addDefinition($this->prefix('objectCreator'))
			->setFactory(ObjectCreator::class, [
				$dependencyInjectorManagerDefinition,
			])
			->setAutowired(false);
	}

	private function registerProcessor(
		ContainerBuilder $builder,
		ServiceDefinition $metaLoaderDefinition,
		ServiceDefinition $ruleManagerDefinition,
		ServiceDefinition $objectCreatorDefinition
	): void
	{
		$builder->addDefinition($this->prefix('processor'))
			->setFactory(DefaultProcessor::class, [
				'metaLoader' => $metaLoaderDefinition,
				'ruleManager' => $ruleManagerDefinition,
				'objectCreator' => $objectCreatorDefinition,
			])
			->setType(Processor::class);
	}

}
