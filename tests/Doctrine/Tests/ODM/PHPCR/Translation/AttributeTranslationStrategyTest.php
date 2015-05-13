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
}
