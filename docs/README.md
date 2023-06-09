# Nette Object Mapper

Orisai Object Mapper integration for Nette

## Content

- [Setup](#setup)
- [Registering custom rules](#registering-custom-rules)
- [Registering dependency injectors](#registering-dependency-injectors)
- [Usage](#usage)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/nette-object-mapper
```

Register extension

```neon
extensions:
	orisai.objectMapper: OriNette\ObjectMapper\DI\ObjectMapperExtension

orisai.objectMapper:
	debug: %debugMode%
```

## Registering custom rules

Register custom rules

```neon
orisai.objectMapper:
	rules:
		# instance of class
		- Example\CustomRule()
		# service 'example.rule'
		- @example.rule
		# service of type 'Example\Rule'
		- @Example\Rule
```

Creating custom rules is explained by [orisai/object-mapper](https://github.com/orisai/object-mapper).

## Registering dependency injectors

Register dependency injectors used to pass dependencies to mapped objects

```neon
orisai.objectMapper:
	dependencyInjectors:
		# instance of class
		- Example\FooDependencyInjector()
		# service 'example.fooDependencyInjector'
		- @example.fooDependencyInjector
		# service of type 'Example\FooDependencyInjector'
		- @Example\FooDependencyInjector
```

Injecting dependencies is explained by [orisai/object-mapper](https://github.com/orisai/object-mapper).

## Usage

Check documentation of [orisai/object-mapper](https://github.com/orisai/object-mapper). Everything except setup is exact
same.
