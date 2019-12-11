<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;
use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;

class AttributeTranslationStrategyTest extends PHPCRTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * @var AttributeTranslationStrategy
     */
    private $strategy;

    public function setUp(): void
    {
        $this->dm = $this->createMock(DocumentManager::class);

        $this->strategy = new AttributeTranslationStrategy($this->dm);
        $this->strategy->setPrefix('test');

        $class = new \ReflectionClass(AttributeTranslationStrategy::class);
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
        $classMetadata = $this->createMock(ClassMetadata::class);
        $document = new \stdClass;
        $localizedPropNames = array(
            'test:de-prop1' => 'de',
            'test:de_at-prop2' => 'de_at',
            'test:en_Hans_CN_nedis_rozaj_x_prv1_prv2-prop3' => 'en_Hans_CN_nedis_rozaj_x_prv1_prv2',
            'i18n:de-asdf' => false, // prefix is incorrect
            'asdf' => false, // no prefix
            'de_asdf' => false, // no property name
        );

        $node = $this->createMock(NodeInterface::class);
        $properties = array();

        foreach (array_keys($localizedPropNames) as $localizedPropName) {
            $property = $this->createMock(PropertyInterface::class);
            $property
                ->method('getName')
                ->willReturn($localizedPropName);
            $properties[] = $property;
        }
        $node
            ->expects($this->once())
            ->method('getProperties')
            ->with('test:*')
            ->willReturn($properties);

        $locales = $this->strategy->getLocalesFor($document, $node, $classMetadata);
        $expected = array_values(array_filter($localizedPropNames, function ($valid) {
            return $valid;
        }));
        $this->assertEquals($expected, $locales);
    }
}
