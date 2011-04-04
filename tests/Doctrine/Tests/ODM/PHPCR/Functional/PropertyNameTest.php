<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

/**
 * @group functional
 */
class PropertyNameTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\TestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testPropertyname()
    {
        $doc = new TestObj();
        $doc->id = '/functional/pn';
        $doc->name = 'Testname';
        $doc->othername = 'Testothername';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('pn')->hasProperty('name'));
        $this->assertTrue($this->node->getNode('pn')->hasProperty('myname'));

        $doc = $this->dm->find($this->type, '/functional/pn');
        $this->assertNotNull($doc->name);
        $this->assertEquals('Testname', $doc->name);
        $this->assertNotNull($doc->othername);
        $this->assertEquals('Testothername', $doc->othername);
    }
}


/**
 * @Document(alias="testObj")
 */
class TestObj
{
    /** @Id */
    public $id;
    /** @Node */
    public $node;
    /** @String */
    public $name;
    /** @String(name="myname") */
    public $othername;
}
