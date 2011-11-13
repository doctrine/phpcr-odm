<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataInfo;

class ClassMetadataInfoTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTypeOfField()
    {
        $cmi = new ClassMetadataInfo('Doctrine\Tests\ODM\PHPCR\Mapping\Person');
        $this->assertEquals(null, $cmi->getTypeOfField('some_field'));
        $cmi->fieldMappings['some_field'] = array('type' => 'some_type');
        $this->assertEquals('some_type', $cmi->getTypeOfField('some_field'));
    }
}
