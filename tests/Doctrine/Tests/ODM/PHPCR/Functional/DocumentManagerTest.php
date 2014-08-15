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
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\User';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testFindManyWithNonExistingUuuid()
    {
        $user = new TestUser();
        $user->username = 'test-name';
        $this->dm->persist($user);

        $uuids[] = $user->uuid;
        $uuids[] = UUIDHelper::generateUUID();

        $documents = $this->dm->findMany('User', $uuids);
        $this->assertEquals(1, count($documents));
    }
}

/**
 * @PHPCRODM\Document()
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
