<?php

namespace Doctrine\Tests\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\Id\ParentIdGenerator;
use Doctrine\ODM\PHPCR\Id\FieldSlugifierIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class FieldSlugifierIdGeneratorTest extends \PHPUnit_Framework_TestCase
{
    private $classMetadata;
    private $documentManager;

    public function setUp()
    {
        $classMetadata = new ClassMetadata('\stdClass');
        $this->classMetadata = $classMetadata;
        $this->documentManager = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\DocumentManager'
        )->disableOriginalConstructor()->getMock();
        $this->parent = new \stdClass();
        $this->document = new \stdClass();
        $this->config = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Configuration'
        );
        $this->generator = new FieldSlugifierIdGenerator($this->config);
    }

    public function provideGenerate()
    {
        return array(
            array(
                array(
                    'expected_exception' => 'Doctrine\ODM\PHPCR\Id\IdException',
                )
            )
        );
    }


    /**
     * @dataProvider provideGenerate
     */
    public function testGenerateNoParent($options)
    {
        $options = array_merge(array(
            'parent' => false,
            'parent_from_field' => false,
            'non_existing_field' => false,
            'empty_value' => false,
            'expected_exception' => null,
        ), $options);

        if ($options['expected_exception']) {
            $this->setExpectedException($options['expected_exception']);
        }

        $this->generator->generate(
            $this->document,
            $this->classMetadata,
            $this->documentManager,
            $options['parent'] ? $this->parent : null
        );
    }

    public static function slugify($string)
    {
        return $string;
    }
}
