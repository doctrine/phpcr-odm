<?php


namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\Util\UUIDHelper;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

class DocumentManagerTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testFindManyWithNonExistingUuuid()
    {
        $user = new TestUser();
        $user->username = 'test-name';
        $user->id = '/functional/test';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $actualUuid = $user->uuid;
        $unusedUuid = UUIDHelper::generateUUID();

        $this->assertNotNull($this->dm->find(get_class($user), $user->id));
        $this->assertNotNull($this->dm->find(get_class($user), $actualUuid));
        $this->assertNull($this->dm->find(get_class($user), $unusedUuid));

        $uuids = array($actualUuid, $unusedUuid);

        $documents = $this->dm->findMany(get_class($user), $uuids);
        $this->assertEquals(1, count($documents));
    }
}

/**
 * @PHPCRODM\Document(referenceable=true)
 *
 */
class TestUser
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\String */
    public $username;

    /** @PHPCRODM\Uuid */
    public $uuid;
}
