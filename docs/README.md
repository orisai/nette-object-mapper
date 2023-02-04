# Nette Object Mapper

Orisai Object Mapper integration for Nette

## Content

- [Setup](#setup)
- [Usage](#usage)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/nette-object-mapper
```

```neon
extensions:
	orisai.objectMapper: OriNette\ObjectMapper\DI\ObjectMapperExtension

orisai.objectMapper:
	debug: %debugMode%
```

## Usage

Check documentation of [orisai/object-mapper](https://github.com/orisai/object-mapper). Everything except setup is exact
same.
