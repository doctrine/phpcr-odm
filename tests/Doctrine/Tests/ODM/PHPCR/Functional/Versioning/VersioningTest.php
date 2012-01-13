<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class VersioningTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\Versioning\VersionTestObj';
        $this->dm = $this->createDocumentManager();

        // Check that the repository supports versioning
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('option.versioning.supported')) {
            $this->markTestSkipped('PHPCR repository doesn\'t support versioning');
        }

        $this->node = $this->resetFunctionalNode($this->dm);

        $versionNode = $this->node->addNode('versionTestObj');
        $versionNode->setProperty('username', 'lsmith');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:class', $this->type);
        $versionNode->addMixin("mix:versionable");

        $this->dm->getPhpcrSession()->save();
        $this->dm = $this->createDocumentManager();
    }

    public function testCheckin()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkin($user);

        $this->assertInstanceOf('PHPCR\NodeInterface', $user->node);
        $this->assertTrue($user->node->isNodeType('mix:simpleVersionable'));

        // TODO: understand why jcr:isCheckedOut is true for a checked in node
        //$this->assertFalse($user->node->getPropertyValue('jcr:isCheckedOut'));
    }

    public function testCheckout()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkin($user);
        $this->dm->checkout($user);
        $user->username = 'nicam';
        $this->dm->checkin($user);
    }

    public function testRestore()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkin($user);
        $this->dm->checkout($user);
        $user->username = 'nicam';

        $this->dm->restore('1.0', $user);
        $user = $this->dm->find($this->type, '/functional/versionTestObj');

        $this->assertEquals('lsmith', $user->username);
    }

    public function testGetAllLinearVersions()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');

        $this->dm->checkpoint($doc);
        $this->dm->checkpoint($doc);
        $this->dm->checkpoint($doc);
        $this->dm->checkpoint($doc);

        $versions = $this->dm->getAllLinearVersions($doc);

        $this->assertEquals(5, count($versions));

        foreach ($versions as $key => $val) {
            $this->assertTrue(isset($val['name']));
            $this->assertTrue(isset($val['labels']));
            $this->assertTrue(isset($val['created']));
            $this->assertTrue(isset($val['createdBy']));

            $this->assertEquals($key, $val['name']);
            $this->assertEmpty($val['labels']); // TODO: change this test once version labels are implemented
            $this->assertInstanceOf('DateTime', $val['created']);
            $this->assertEmpty($val['createdBy']); // TODO: change this test once we have the version creator
        }
    }
}

/**
 * @PHPCRODM\Document(alias="versionTestObj", versionable="full")
 */
class VersionTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Version */
    public $version;
    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}
