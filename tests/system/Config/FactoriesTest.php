<?php

namespace CodeIgniter\Config;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Widgets\OtherWidget;
use Tests\Support\Widgets\SomeWidget;
use ReflectionClass;
use stdClass;

class FactoriesTest extends CIUnitTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		Factories::reset();
	}

	protected function getFactoriesStaticProperty(...$params)
	{
		// First parameter is the actual property
		$name = array_shift($params);

		$factory    = new ReflectionClass(Factories::class);
		$properties = $factory->getStaticProperties();
		$property   = $properties[$name] ?? [];

		// If any additional parameters were provided then drill into the array
		foreach ($params as $param)
		{
			$property = $property[$param];
		}

		return $property;
	}

	//--------------------------------------------------------------------

	public function testGetsOptions()
	{
		$result = Factories::getOptions('models');

		$this->assertTrue($result['preferApp']);
	}

	public function testGetsDefaultOptions()
	{
		$result = Factories::getOptions('blahblahs');

		$this->assertTrue($result['preferApp']);
		$this->assertEquals('Blahblahs', $result['path']);
	}

	public function testSetsOptions()
	{
		Factories::setOptions('widgets', ['foo' => 'bar']);

		$result = Factories::getOptions('widgets');

		$this->assertEquals('bar', $result['foo']);
		$this->assertEquals(true, $result['preferApp']);
	}

	public function testUsesConfigOptions()
	{
		// Simulate having a $widgets property in App\Config\Factory
		$config          = new Factory();
		$config->widgets = ['bar' => 'bam'];
		Factories::injectMock('config', Factory::class, $config);

		$result = Factories::getOptions('widgets');

		$this->assertEquals('bam', $result['bar']);
	}

	public function testSetOptionsResets()
	{
		Factories::injectMock('widgets', 'Banana', new stdClass());

		$result = $this->getFactoriesStaticProperty('instances');
		$this->assertIsArray($result);
		$this->assertArrayHasKey('widgets', $result);

		Factories::setOptions('widgets', []);

		$result = $this->getFactoriesStaticProperty('instances');
		$this->assertIsArray($result);
		$this->assertArrayNotHasKey('widgets', $result);
	}

	public function testResetsAll()
	{
		Factories::setOptions('widgets', ['foo' => 'bar']);

		Factories::reset();

		$result = $this->getFactoriesStaticProperty('options');
		$this->assertEquals([], $result);
	}

	public function testResetsComponentOnly()
	{
		Factories::setOptions('widgets', ['foo' => 'bar']);
		Factories::setOptions('spigots', ['bar' => 'bam']);

		Factories::reset('spigots');

		$result = $this->getFactoriesStaticProperty('options');
		$this->assertIsArray($result);
		$this->assertArrayHasKey('widgets', $result);
	}

	//--------------------------------------------------------------------

	public function testGetsBasenameByBasename()
	{
		$this->assertEquals('SomeWidget', Factories::getBasename('SomeWidget'));
	}

	public function testGetsBasenameByClassname()
	{
		$this->assertEquals('SomeWidget', Factories::getBasename(SomeWidget::class));
	}

	public function testGetsBasenameByAbsoluteClassname()
	{
		$this->assertEquals('UserModel', Factories::getBasename('\Tests\Support\Models\UserModel'));
	}

	public function testGetsBasenameInvalid()
	{
		$this->assertEquals('', Factories::getBasename('Tests\\Support\\'));
	}

	//--------------------------------------------------------------------

	public function testCreatesByBasename()
	{
		$result = Factories::widgets('SomeWidget', false);

		$this->assertInstanceOf(SomeWidget::class, $result);
	}

	public function testCreatesByClassname()
	{
		$result = Factories::widgets(SomeWidget::class, false);

		$this->assertInstanceOf(SomeWidget::class, $result);
	}

	public function testCreatesByAbsoluteClassname()
	{
		$result = Factories::models('\Tests\Support\Models\UserModel', false);

		$this->assertInstanceOf('Tests\Support\Models\UserModel', $result);
	}

	public function testCreatesInvalid()
	{
		$result = Factories::widgets('gfnusvjai', false);

		$this->assertNull($result);
	}

	public function testIgnoresNonClass()
	{
		$result = Factories::widgets('NopeWidget', false);

		$this->assertNull($result);
	}

	public function testReturnsSharedInstance()
	{
		$widget1 = Factories::widgets('SomeWidget');
		$widget2 = Factories::widgets(SomeWidget::class);

		$this->assertSame($widget1, $widget2);
	}

	public function testInjection()
	{
		Factories::injectMock('widgets', 'Banana', new stdClass());

		$result = Factories::widgets('Banana');

		$this->assertInstanceOf(stdClass::class, $result);
	}

	//--------------------------------------------------------------------

	public function testRespectsComponentAlias()
	{
		Factories::setOptions('tedwigs', ['component' => 'widgets']);

		$result = Factories::tedwigs('SomeWidget');
		$this->assertInstanceOf(SomeWidget::class, $result);
	}

	public function testRespectsPath()
	{
		Factories::setOptions('models', ['path' => 'Widgets']);

		$result = Factories::models('SomeWidget');
		$this->assertInstanceOf(SomeWidget::class, $result);
	}

	public function testRespectsInstanceOf()
	{
		Factories::setOptions('widgets', ['instanceOf' => stdClass::class]);

		$result = Factories::widgets('SomeWidget');
		$this->assertInstanceOf(SomeWidget::class, $result);

		$result = Factories::widgets('OtherWidget');
		$this->assertNull($result);
	}

	public function testFindsAppFirst()
	{
		// Create a fake class in App
		$class = 'App\Widgets\OtherWidget';
		if (! class_exists($class))
		{
			class_alias(SomeWidget::class, $class);
		}

		$result = Factories::widgets('OtherWidget');
		$this->assertInstanceOf(SomeWidget::class, $result);
	}

	public function testpreferAppOverridesClassname()
	{
		// Create a fake class in App
		$class = 'App\Widgets\OtherWidget';
		if (! class_exists($class))
		{
			class_alias(SomeWidget::class, $class);
		}

		$result = Factories::widgets(OtherWidget::class);
		$this->assertInstanceOf(SomeWidget::class, $result);

		Factories::setOptions('widgets', ['preferApp' => false]);

		$result = Factories::widgets(OtherWidget::class);
		$this->assertInstanceOf(OtherWidget::class, $result);
	}
}
