# Nette Object Mapper

Orisai Object Mapper integration for Nette

## Content

- [Setup](#setup)
- [Registering custom rules](#registering-custom-rules)
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

Register rules in format `Fqn\Of\RuleClass: @rule.definition`

```neon
orisai.objectMapper:
	rules:
		# instance of class
		Example\CustomRule: Example\CustomRule()
		# service 'example.rule'
		Example\CustomRule: @example.rule
```

## Usage

Check documentation of [orisai/object-mapper](https://github.com/orisai/object-mapper). Everything except setup is exact
same.
