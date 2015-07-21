<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;
use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

class AttributeTranslationStrategyTest extends PHPCRTestCase
{
    private $dm;
    private $method;
    private $strategy;

    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new AttributeTranslationStrategy($this->dm);
        $this->strategy->setPrefix('test');

        $class = new \ReflectionClass('Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy');
        $this->method = $class->getMethod('getTranslatedPropertyName');
        $this->method->setAccessible(true);
    }

    public function testSetPrefixAndGetPropertyName()
    {
        $name = $this->method->invokeArgs($this->strategy, array('fr', 'field'));
        $this->assertEquals('test:fr-field', $name);
    }

    public function testSubRegion()
    {
        $name = $this->method->invokeArgs($this->strategy, array('en_GB', 'field'));
        $this->assertEquals('test:en_GB-field', $name);
    }

    public function testGetLocalesFor()
    {
        if (version_compare(PHP_VERSION, '5.3.3', '<=')) {
            $this->markTestSkipped('Prophesize does not work on PHP 5.3.3');
        }

        $classMetadata = $this->prophesize('Doctrine\ODM\PHPCR\Mapping\ClassMetadata');
        $document = new \stdClass;
        $localizedPropNames = array(
            'test:de-prop1' => 'de',
            'test:de_at-prop2' => 'de_at',
            'test:en_Hans_CN_nedis_rozaj_x_prv1_prv2-prop3' => 'en_Hans_CN_nedis_rozaj_x_prv1_prv2',
            'i18n:de-asdf' => false, // prefix is incorrect
            'asdf' => false, // no prefix
            'de_asdf' => false, // no property name
        );

        $node = $this->prophesize('PHPCR\NodeInterface');
        $properties = array();

        foreach (array_keys($localizedPropNames) as $localizedPropName) {
            $property = $this->prophesize('PHPCR\PropertyInterface');
            $property->getName()->willReturn($localizedPropName);
            $properties[] = $property->reveal();
        }
        $node->getProperties('test:*')->willReturn($properties);

        $locales = $this->strategy->getLocalesFor($document, $node->reveal(), $classMetadata->reveal());
        $expected = array_values(array_filter($localizedPropNames, function ($valid) { return $valid; }));
        $this->assertEquals($expected, $locales);
    }
}
