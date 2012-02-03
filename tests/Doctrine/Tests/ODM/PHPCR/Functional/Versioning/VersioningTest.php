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

    public function testRestoreVersion()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($user);
        $user->username = 'nicam';
        $this->dm->flush();

        $versions = $this->dm->getAllLinearVersions($user);
        each($versions);
        list($dummy, $versionInfo) = each($versions);
        $versionName = $versionInfo['name'];
        $versionDocument = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $versionName);
        $this->dm->restoreVersion($versionDocument);

        $this->assertEquals('lsmith', $user->username);

        $this->dm->clear();
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

        $this->assertCount(5, $versions);

        foreach ($versions as $key => $val) {
            $this->assertTrue(isset($val['name']));
            $this->assertTrue(isset($val['labels']));
            $this->assertTrue(isset($val['created']));

            $this->assertEquals($key, $val['name']);
            // TODO: test once version labels are implemented
            // $this->assertEmpty($val['labels']);
            $this->assertInstanceOf('DateTime', $val['created']);
        }
    }

    /**
     * Test it's not possible to get a version of a non-versionable document
     * @expectedException \InvalidArgumentException
     */
    public function testFindVersionByNameNotVersionable()
    {
        $session = $this->dm->getPhpcrSession();
        $node = $session->getNode('/functional')->addNode('noVersionTestObj');
        $session->save();
        $id = $node->getPath();
        $this->dm->findVersionByName($this->type, $id, 'whatever');
    }

    /**
     * Test that trying to read a non existing version fails
     * @expectedException \InvalidArgumentException
     */
    public function testFindVersionByNameVersionDoesNotExist()
    {
        $this->dm->findVersionByName($this->type, '/functional/versionTestObj', 'whatever');
    }

    public function testFindVersionByName()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $lastVersionName);

        $this->assertEquals('lsmith', $frozenDocument->username);
        $this->assertEquals(array(3,1,2), iterator_to_array($frozenDocument->numbers));

        $this->assertEquals($lastVersionName, $frozenDocument->versionName);
        $this->assertInstanceOf('DateTime', $frozenDocument->versionCreated);
        $this->assertTrue(time() - $frozenDocument->versionCreated->getTimestamp() < 100);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testPersistVersionError()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $lastVersionName);

        $this->dm->persist($frozenDocument);
    }

    /**
     * The version is detached and not tracked anymore.
     */
    public function testModifyVersion()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $lastVersionName);

        $doc->username = 'original';
        $frozenDocument->username = 'changed';
        $this->dm->flush();
        $this->dm->clear();
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->assertEquals('original', $doc->username);
    }

    /**
     * Check we cannot remove the last version of a document (since it's the current version)
     * @expectedException \PHPCR\ReferentialIntegrityException
     */
    public function testRemoveLastVersion()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $version = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $lastVersionName);
        $this->assertNotNull($version);
        $this->dm->removeVersion($version);
    }

    /**
     * Check we cannot remove the root version of a document
     * @expectedException \PHPCR\Version\VersionException
     */
    public function testRemoveRootVersion()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $firstVersion = reset($linearVersionHistory);
        $firstVersionName = $firstVersion['name'];

        $version = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $firstVersionName);
        $this->assertNotNull($version);
        $this->dm->removeVersion($version);
    }

    public function testRemoveVersion()
    {
        $doc = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        // Create a new version so that we are not trying to remove the last version
        $this->dm->checkpoint($doc);

        // Remove the version
        $version = $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $lastVersionName);
        $removedVersionPath = $version->id;

        $this->dm->removeVersion($version);

        // Check it's not in the history anymore
        $this->assertFalse($this->dm->getPhpcrSession()->nodeExists(dirname($removedVersionPath)));

        return $lastVersionName;
    }

    /**
     * Check the version we removed in testRemoveVersion does not exist anymore
     * @depends testRemoveVersion
     * @expectedException \InvalidArgumentException
     */
    public function testDeletedVersionDoesNotExistAnymore($lastVersionName)
    {
        $this->dm->findVersionByName($this->type, '/functional/versionTestObj', $lastVersionName);
    }
}

/**
 * @PHPCRODM\Document(versionable="full")
 */
class VersionTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\VersionName */
    public $versionName;

    /** @PHPCRODM\VersionCreated */
    public $versionCreated;

    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}
