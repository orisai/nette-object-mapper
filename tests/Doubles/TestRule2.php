<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Doubles;

use Orisai\ObjectMapper\Args\Args;
use Orisai\ObjectMapper\Args\EmptyArgs;
use Orisai\ObjectMapper\Processing\Context\DynamicContext;
use Orisai\ObjectMapper\Processing\Context\PropertyContext;
use Orisai\ObjectMapper\Processing\Context\ServicesContext;
use Orisai\ObjectMapper\Rules\NoArgsRule;
use Orisai\ObjectMapper\Rules\Rule;
use Orisai\ObjectMapper\Types\SimpleValueType;
use Orisai\ObjectMapper\Types\Type;

/**
 * @implements Rule<EmptyArgs>
 */
class TestRule2 implements Rule
{

	use NoArgsRule;

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function processValue(
		$value,
		Args $args,
		ServicesContext $services,
		PropertyContext $property,
		DynamicContext $dynamic
	)
	{
		return $value;
	}

	public function createType(
		Args $args,
		ServicesContext $services,
		DynamicContext $dynamic
	): Type
	{
		return new SimpleValueType('test2');
	}

}
