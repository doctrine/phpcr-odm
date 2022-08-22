<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\Models\Versioning\FullVersionableArticle;
use Doctrine\Tests\Models\Versioning\FullVersionableArticleWithChildren;
use Doctrine\Tests\Models\Versioning\NonVersionableArticle;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\ReferentialIntegrityException;
use PHPCR\Util\PathHelper;
use PHPCR\Version\VersionException;

/**
 * @group functional
 */
abstract class VersioningTestAbstract extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * class name.
     *
     * @var string
     */
    protected $typeVersion;

    /**
     * class name.
     *
     * @var string
     */
    private $typeReference;

    public function setUp(): void
    {
        $this->typeReference = ReferenceTestObj::class;
        $this->dm = $this->createDocumentManager();

        // Check that the repository supports versioning
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('option.versioning.supported')) {
            $this->markTestSkipped('PHPCR repository does not support versioning');
        }

        $node = $this->resetFunctionalNode($this->dm);

        $versionNode = $node->addNode('versionTestObj');
        $versionNode->setProperty('username', 'lsmith');
        $versionNode->setProperty('numbers', [3, 1, 2]);
        $versionNode->setProperty('phpcr:class', $this->typeVersion);
        $versionNode->addMixin('mix:versionable');

        $referenceNode = $node->addNode('referenceTestObj');
        $referenceNode->setProperty('content', 'reference test');
        $referenceNode->setProperty('phpcr:class', $this->typeReference);
        $referenceNode->addMixin('mix:referenceable');

        $this->dm->getPhpcrSession()->save();

        $versionNodeWithReference = $node->addNode('versionTestObjWithReference');
        $versionNodeWithReference->setProperty('username', 'laupifrpar');
        $versionNodeWithReference->setProperty('numbers', [6, 4, 5]);
        $versionNodeWithReference->setProperty('reference', $referenceNode);
        $versionNodeWithReference->addMixin('mix:versionable');

        $this->dm->getPhpcrSession()->save();
        $this->dm = $this->createDocumentManager();
    }

    public function testCheckinOnNonVersionableNode(): void
    {
        $contentNode = $this->dm->find(
            $this->typeReference,
            '/functional/referenceTestObj'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The document at path \'/functional/referenceTestObj\' is not versionable');
        $this->dm->checkin($contentNode);
    }

    public function testMergeVersionable(): void
    {
        $versionableArticle = new FullVersionableArticle();
        $versionableArticle->setText('very interesting content');
        $versionableArticle->author = 'greg0ire';
        $versionableArticle->topic = 'whatever';
        $versionableArticle->id = '/functional/whatever';
        $versionableArticle->versionName = 'v1';

        $this->dm->persist($versionableArticle);
        $this->dm->flush();
        $this->dm->clear();
        $versionableArticle->versionName = 'v2';

        $mergedVersionableArticle = $this->dm->merge($versionableArticle);
        $this->assertEquals('v2', $mergedVersionableArticle->versionName);
    }

    public function testCheckin(): void
    {
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkin($user);

        $this->assertInstanceOf(NodeInterface::class, $user->node);
        $this->assertTrue($user->node->isNodeType('mix:simpleVersionable'));

        // TODO: understand why jcr:isCheckedOut is true for a checked in node
        //$this->assertFalse($user->node->getPropertyValue('jcr:isCheckedOut'));
    }

    public function testCheckout(): void
    {
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkin($user);
        $this->dm->checkout($user);
        $user->username = 'nicam';
        $this->dm->checkin($user);
        $this->markTestIncomplete('this test has no assertions');
    }

    public function testRestoreVersion(): void
    {
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($user);
        $user->username = 'nicam';
        $this->dm->flush();

        $versions = $this->dm->getAllLinearVersions($user);
        $versionInfo = next($versions);
        $versionName = $versionInfo['name'];
        $versionDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $versionName);
        $this->dm->restoreVersion($versionDocument);

        $this->assertEquals('lsmith', $user->username);

        $this->dm->clear();
        $user = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->assertEquals('lsmith', $user->username);
    }

    public function testGetAllLinearVersions(): void
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
     * Test it's not possible to get a version of a non-versionable document.
     */
    public function testFindVersionByNameNotVersionable(): void
    {
        $session = $this->dm->getPhpcrSession();
        $node = $session->getNode('/functional')->addNode('noVersionTestObj');
        $session->save();
        $id = $node->getPath();

        $this->expectException(InvalidArgumentException::class);
        $this->dm->findVersionByName($this->typeVersion, $id, 'whatever');
    }

    /**
     * Test that trying to read a non existing version fails.
     */
    public function testFindVersionByNameVersionDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', 'whatever');
    }

    public function testFindVersionByName(): void
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);

        $this->assertEquals('lsmith', $frozenDocument->username);
        $this->assertEquals([3, 1, 2], $frozenDocument->numbers);

        $this->assertEquals($lastVersionName, $frozenDocument->versionName);
        $this->assertInstanceOf('DateTime', $frozenDocument->versionCreated);
        $this->assertTrue(time() - $frozenDocument->versionCreated->getTimestamp() < 100);
    }

    public function testFindVersionByNameWithReference(): void
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

    public function testPersistVersionError(): void
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $frozenDocument = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);

        $this->expectException(InvalidArgumentException::class);
        $this->dm->persist($frozenDocument);
    }

    /**
     * The version is detached and not tracked anymore.
     */
    public function testModifyVersion(): void
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
     * Check we cannot remove the last version of a document (since it's the current version).
     */
    public function testRemoveLastVersion(): void
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $lastVersion = end($linearVersionHistory);
        $lastVersionName = $lastVersion['name'];

        $version = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);
        $this->assertNotNull($version);

        $this->expectException(ReferentialIntegrityException::class);
        $this->dm->removeVersion($version);
    }

    /**
     * Check we cannot remove the root version of a document.
     */
    public function testRemoveRootVersion(): void
    {
        $doc = $this->dm->find($this->typeVersion, '/functional/versionTestObj');
        $this->dm->checkpoint($doc);

        $linearVersionHistory = $this->dm->getAllLinearVersions($doc);
        $firstVersion = reset($linearVersionHistory);
        $firstVersionName = $firstVersion['name'];

        $version = $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $firstVersionName);
        $this->assertNotNull($version);

        $this->expectException(VersionException::class);
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
     * Check the version we removed in testRemoveVersion does not exist anymore.
     *
     * @depends testRemoveVersion
     */
    public function testDeletedVersionDoesNotExistAnymore($lastVersionName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->findVersionByName($this->typeVersion, '/functional/versionTestObj', $lastVersionName);
    }

    /**
     * Try to access the children of a specific version of a document and assert they
     * are hydrated properly.
     */
    public function testUnversionedChildrenOnParentVersion(): void
    {
        $versionableArticle = new FullVersionableArticleWithChildren();
        $versionableArticle->author = 'mellowplace';
        $versionableArticle->topic = 'children test';
        $versionableArticle->id = '/functional/children-test';
        $versionableArticle->setText('Parent article text');
        $this->dm->persist($versionableArticle);

        $childArticle = new NonVersionableArticle();
        $childArticle->setText('This is the child');
        $childArticle->id = '/functional/children-test/child';
        $childArticle->author = 'mellowplace';
        $childArticle->topic = 'children test - child';
        $versionableArticle->addChildArticle($childArticle);

        // checkin the first version (1.0)
        $this->dm->flush();
        $this->dm->checkpoint($versionableArticle);

        // now modify the child nodes text and checkin the second version (1.1)
        $childArticle->setText('modified text');
        $this->dm->flush();
        $this->dm->checkpoint($versionableArticle);

        $firstVersion = $this->dm->findVersionByName(
            FullVersionableArticleWithChildren::class,
            $versionableArticle->id,
            '1.0'
        );

        $secondVersion = $this->dm->findVersionByName(
            FullVersionableArticleWithChildren::class,
            $versionableArticle->id,
            '1.1'
        );

        $this->assertEquals(
            'This is the child',
            $firstVersion->childArticles->first()->getText(),
            'The expected child article text is correct'
        );

        $this->assertEquals(
            'modified text',
            $secondVersion->childArticles->first()->getText(),
            'The expected, modified, child article text is correct'
        );
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

    /** @PHPCRODM\Field(type="string", property="username") */
    public $content;
}
