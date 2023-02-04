<?php declare(strict_types = 1);

namespace Tests\OriNette\ObjectMapper\Unit\DI;

use Nette\DI\Container;
use OriNette\ObjectMapper\DI\LazyMetaSourceManager;
use PHPUnit\Framework\TestCase;

final class LazyMetaSourceManagerTest extends TestCase
{

	public function testDefaults(): void
	{
		$manager = new LazyMetaSourceManager(new Container());
		self::assertSame([], $manager->getAll());
	}

}
