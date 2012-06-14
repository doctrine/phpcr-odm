<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use PHPCR\PropertyType;

/**
 * @group functional
 */
class PropertyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\PropertyTestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testPropertyname()
    {
        $doc = new PropertyTestObj();
        $doc->id = '/functional/p';
        $doc->string = 'astring';
        $doc->long = 123;
        $doc->int = 321;
        $doc->decimal = '343';
        $doc->double = 3.14;
        $doc->float = 2.8;
        $date = new \DateTime();
        $doc->date = $date;
        $doc->boolean = true;
        $doc->name = 'aname';
        $doc->path = '../a';
        $doc->uri = 'http://cmf.symfony.com:8080/about.html#there';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('p')->hasProperty('string'));
        $this->assertEquals(PropertyType::STRING, $this->node->getNode('p')->getProperty('string')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('long'));
        $this->assertEquals(PropertyType::LONG, $this->node->getNode('p')->getProperty('long')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('int'));
        $this->assertEquals(PropertyType::LONG, $this->node->getNode('p')->getProperty('int')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('decimal'));
        $this->assertEquals(PropertyType::DECIMAL, $this->node->getNode('p')->getProperty('decimal')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('double'));
        $this->assertEquals(PropertyType::DOUBLE, $this->node->getNode('p')->getProperty('double')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('float'));
        $this->assertEquals(PropertyType::DOUBLE, $this->node->getNode('p')->getProperty('float')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('date'));
        $this->assertEquals(PropertyType::DATE, $this->node->getNode('p')->getProperty('date')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('boolean'));
        $this->assertEquals(PropertyType::BOOLEAN, $this->node->getNode('p')->getProperty('boolean')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('name'));
        $this->assertEquals(PropertyType::NAME, $this->node->getNode('p')->getProperty('name')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('path'));
        $this->assertEquals(PropertyType::PATH, $this->node->getNode('p')->getProperty('path')->getType());
        
        $this->assertTrue($this->node->getNode('p')->hasProperty('uri'));
        $this->assertEquals(PropertyType::URI, $this->node->getNode('p')->getProperty('uri')->getType());

        $doc = $this->dm->find($this->type, '/functional/p');
        $this->assertNotNull($doc->string);
        $this->assertEquals('astring', $doc->string);
        $this->assertNotNull($doc->long);
        $this->assertEquals(123, $doc->long);
        $this->assertNotNull($doc->int);
        $this->assertEquals(321, $doc->int);
        $this->assertNotNull($doc->decimal);
        $this->assertEquals('343', $doc->decimal);
        $this->assertNotNull($doc->double);
        $this->assertEquals(3.14, $doc->double);
        $this->assertNotNull($doc->float);
        $this->assertEquals(2.8, $doc->float);
        $this->assertNotNull($doc->date);
        $this->assertEquals($date->getTimestamp(), $doc->date->getTimestamp());
        $this->assertNotNull($doc->boolean);
        $this->assertEquals(true, $doc->boolean);
        $this->assertNotNull($doc->name);
        $this->assertEquals('aname', $doc->name);
        $this->assertNotNull($doc->path);
        $this->assertEquals('../a', $doc->path);
        $this->assertNotNull($doc->uri);
        $this->assertEquals('http://cmf.symfony.com:8080/about.html#there', $doc->uri);
    }
}

/**
 * @PHPCRODM\Document()
 */
class PropertyTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String */
    public $string;
    //binary tested in its own test
    /** @PHPCRODM\Long */
    public $long;
    /** @PHPCRODM\Int */
    public $int;
    /** @PHPCRODM\Decimal */
    public $decimal;
    /** @PHPCRODM\Double */
    public $double;
    /** @PHPCRODM\Float */
    public $float;
    /** @PHPCRODM\Date */
    public $date;
    /** @PHPCRODM\Boolean */
    public $boolean;
    /** @PHPCRODM\Name */
    public $name;
    /** @PHPCRODM\Path */
    public $path;
    /** @PHPCRODM\Uri */
    public $uri;
}
