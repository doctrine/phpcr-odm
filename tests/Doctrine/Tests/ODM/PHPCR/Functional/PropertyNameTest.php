<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;

/**
 * @group functional
 */
class PropertyNameTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     *
     * @var string
     */
    private $type = TestObj::class;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
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
 * @PHPCRODM\Document()
 */
class TestObj
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\Field(type="string") */
    public $name;

    /** @PHPCRODM\Field(type="string", property="myname") */
    public $othername;
}
