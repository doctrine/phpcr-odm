<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\ODM\PHPCR\UnitOfWork;

class DetachTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $this->type = 'Doctrine\Tests\Models\CMS\CmsUser';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('lsmith');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('phpcr:class', $this->type, \PHPCR\PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testDetachNewObject()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->detach($user);

        $this->dm->persist($user);
        $this->dm->flush();

        $check = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals('beberlei', $check->username);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testDetachedKnownObject()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->detach($user);
        $this->dm->persist($user);
    }

    public function testDetach()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $newUser = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertEquals('lsmith', $newUser->username);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testDetachWithPerist()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->persist($user);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testDetachWithMove()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->move($user, '/functional/user2');
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testDetachWithRemove()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->remove($user);
    }

    public function testDetachWithChildren()
    {
        $parent = $this->dm->find(null, '/functional');

        $this->dm->detach($parent);
    }
}
