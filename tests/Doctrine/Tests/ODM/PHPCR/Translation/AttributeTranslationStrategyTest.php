<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;

use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

class AttributeTranslationStrategyTest extends PHPCRTestCase
{
    public function testSetPrefixAndGetPropertyName()
    {
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $s = new AttributeTranslationStrategy($dm);
        $s->setPrefix('test');

        $class = new \ReflectionClass('Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy');
        $method = $class->getMethod('getTranslatedPropertyName');
        $method->setAccessible(true);

        $name = $method->invokeArgs($s, array('fr', 'field'));
        $this->assertEquals('test:fr-field', $name);
    }
}
