<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Doubles;

use Orisai\ObjectMapper\Args\Args;
use Orisai\ObjectMapper\Context\FieldContext;
use Orisai\ObjectMapper\Context\TypeContext;
use Orisai\ObjectMapper\Rules\NoArgsRule;
use Orisai\ObjectMapper\Types\SimpleValueType;
use Orisai\ObjectMapper\Types\Type;

final class ExtendingTestRule extends TestRule
{

	use NoArgsRule;

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function processValue($value, Args $args, FieldContext $context)
	{
		return $value;
	}

	public function createType(Args $args, TypeContext $context): Type
	{
		return new SimpleValueType('test');
	}

}
