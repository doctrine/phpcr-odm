<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use PHPCR\Util\PathHelper;
use Doctrine\Tests\Models\Versioning\FullVersionableArticle;

/**
 * @group functional
 */
abstract class VersioningTestAbstract extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * class name
     *
     * @var string
     */
    protected $typeVersion;

    /**
     * class name
     *
     * @var string
     */
    private $typeReference;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->typeReference = 'Doctrine\Tests\ODM\PHPCR\Functional\Versioning\ReferenceTestObj';
        $this->dm = $this->createDocumentManager();

        // Check that the repository supports versioning
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('option.versioning.supported')) {
            $this->markTestSkipped('PHPCR repository does not support versioning');
        }

        $this->node = $this->resetFunctionalNode($this->dm);

        $versionNode = $this->node->addNode('versionTestObj');
        $versionNode->setProperty('username', 'lsmith');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:class', $this->typeVersion);
        $versionNode->addMixin("mix:versionable");

        $referenceNode = $this->node->addNode('referenceTestObj');
        $referenceNode->setProperty('content', 'reference test');
        $referenceNode->setProperty('phpcr:class', $this->typeReference);
        $referenceNode->addMixin("mix:referenceable");

        $this->dm->getPhpcrSession()->save();

        $versionNodeWithReference = $this->node->addNode('versionTestObjWithReference');
        $versionNodeWithReference->setProperty('username', 'laupifrpar');
        $versionNodeWithReference->setProperty('numbers', array(6, 4, 5));
        $versionNodeWithReference->setProperty('reference', $referenceNode);
        $versionNodeWithReference->addMixin("mix:versionable");

        $this->dm->getPhpcrSession()->save();
        $this->dm = $this->createDocumentManager();
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     * @expectedMessage The document at path '/functional/referenceTestObj' is not versionable
     */
    public function testCheckinOnNonVersionableNode()
    {
        $contentNode = $this->dm->find(
            $this->typeReference,
            '/functional/referenceTestObj'
        );
        $this->dm->checkin($contentNode);
    }

    public function testMergeVersionable()
    {
        $versionableArticle = new FullVersionableArticle;
        $versionableArticle->setText('very interesting content');
        $versionableArticle->author = 'greg0ire';
        $versionableArticle->topic  = 'whatever';
        $versionableArticle->id = '/functional/whatever';
        $versionableArticle->versionName = 'v1';

        $this->dm->persist($versionableArticle);
        $this->dm->flush();
        $this->dm->clear();
        $versionableArticle->versionName = 'v2';

        $mergedVersionableArticle = $this->dm->merge($versionableArticle);
        $this->assertEquals('v2', $mergedVersionableArticle->versionName);
    }

    public function testCheckin()
    {
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkin($user);

        $this->assertInstanceOf('PHPCR\NodeInterface', $user->node);
        $this->assertTrue($user->node->isNodeType('mix:simpleVersionable'));

        // TODO: understand why jcr:isCheckedOut is true for a checked in node
        //$this->assertFalse($user->node->getPropertyValue('jcr:isCheckedOut'));
    }

    public function testCheckout()
    {
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkin($user);
        $this->dm->checkout($user);
        $user->username = 'nicam';
        $this->dm->checkin($user);
        $this->markTestIncomplete('this test has no assertions');
    }

    public function testRestoreVersion()
    {
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($user);
        $user->username = 'nicam';
        $this->dm->flush();

        $versions = $this->dm->getAllLinearVersions($user);
        each($versions);
        list($dummy, $versionInfo) = each($versions);
        $versionName = $versionInfo['name'];
        $versionDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $versionName);
        $this->dm->restoreVersion($versionDocument);

        $this->assertEquals('lsmith', $user->username);

        $this->dm->clear();
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->assertEquals('lsmith', $user->username);
    }

    public function testGetAllLinearVersions()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');

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
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testFindVersionByNameNotVersionable()
    {
        $session = $this->dm->getPhpcrSession();
        $node = $session->getNode('/functional')->addNode('noVersionTestObj');
        $session->save();
        $id = $node->getPath();
        $this->dm->findVersionByName($this->typeVersion, $id, 'whatever');
    }

    /**
     * Test that trying to read a non existing version fails
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testFindVersionByNameVersionDoesNotExist()
    {
        $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', 'whatever');
    }

    public function testFindVersionByName()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);

        $this->assertEquals('lsmith', $frozenDocument->username);
        $this->assertEquals(array(3,1,2), $frozenDocument->numbers);

        $this->assertEquals($lastVersionName, $frozenDocument->versionName);
        $this->assertInstanceOf('DateTime', $frozenDocument->versionCreated);
        $this->assertTrue(time() - $frozenDocument->versionCreated->getTimestamp() < 100);
    }

    public function testFindVersionByNameWithReference()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObjWithReference');
        $this->dm->checkpoint($doc); // Create a new version 1.0

        // Delete the reference in the doc, persist and checkpoint the doc
        $doc->reference = null;
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->checkpoint($doc); // Create a new version 1.1

        // Remove the reference obj
        $referenceDoc = $this->dm->find($this->typeReference, '/functional/referenceTestObj');
        $this->dm->remove($referenceDoc);
        $this->dm->flush();
        $this->dm->clear();

        $frozenDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObjWithReference', '1.0');
        $this->assertNull($frozenDocument->reference);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testPersistVersionError()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);

        $this->dm->persist($frozenDocument);
    }

    /**
     * The version is detached and not tracked anymore.
     */
    public function testModifyVersion()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);

        $doc->username = 'original';
        $frozenDocument->username = 'changed';
        $this->dm->flush();
        $this->dm->clear();
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->assertEquals('original', $doc->username);
    }

    /**
     * Check we cannot remove the last version of a document (since it's the current version)
     * @expectedException \PHPCR\ReferentialIntegrityException
     */
    public function testRemoveLastVersion()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $version = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);
        $this->assertNotNull($version);
        $this->dm->removeVersion($version);
    }

    /**
     * Check we cannot remove the root version of a document
     * @expectedException \PHPCR\Version\VersionException
     */
    public function testRemoveRootVersion()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $firstVersion = reset($linearVersionHistory);
        $firstVersionName = $firstVersion['name'];

        $version = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $firstVersionName);
        $this->assertNotNull($version);
        $this->dm->removeVersion($version);
    }

    public function testRemoveVersion()
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        // Create a new version so that we are not trying to remove the last version
        $this->dm->checkpoint($doc);

        // Remove the version
        $version = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);
        $removedVersionPath = $version->id;

        $this->dm->removeVersion($version);

        // Check it's not in the history anymore
        $this->assertFalse($this->dm->getPhpcrSession()->nodeExists(PathHelper::getParentPath($removedVersionPath)));

        return $lastVersionName;
    }

    /**
     * Check the version we removed in testRemoveVersion does not exist anymore
     * @depends testRemoveVersion
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testDeletedVersionDoesNotExistAnymore($lastVersionName)
    {
        $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);
    }
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferenceTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\String(property="username") */
    public $content;
}

