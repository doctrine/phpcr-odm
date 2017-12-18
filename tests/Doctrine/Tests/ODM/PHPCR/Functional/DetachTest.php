<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

class DetachTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class.
     *
     * @var string
     */
    private $type;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->type = CmsUser::class;
        $this->dm = $this->createDocumentManager([__DIR__]);
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('lsmith');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', [3, 1, 2]);
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testDetachNewObject()
    {
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $this->dm->detach($user);

        $this->dm->persist($user);
        $this->dm->flush();

        $check = $this->dm->find(CmsUser::class, $user->id);
        $this->assertEquals('beberlei', $check->username);
    }

    public function testDetachedKnownObject()
    {
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->detach($user);

        $this->expectException(\Doctrine\ODM\PHPCR\Exception\InvalidArgumentException::class);
        $this->dm->persist($user);
    }

    public function testDetach()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $newUser = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertEquals('lsmith', $newUser->username);
    }

    public function testDetachWithPerist()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);

        $this->expectException(\Doctrine\ODM\PHPCR\Exception\InvalidArgumentException::class);
        $this->dm->persist($user);
    }

    public function testDetachWithMove()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);

        $this->expectException(\Doctrine\ODM\PHPCR\Exception\InvalidArgumentException::class);
        $this->dm->move($user, '/functional/user2');
    }

    public function testDetachWithRemove()
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);

        $this->expectException(\Doctrine\ODM\PHPCR\Exception\InvalidArgumentException::class);
        $this->dm->remove($user);
    }

    public function testDetachWithChildren()
    {
        $parent = $this->dm->find(null, '/functional');

        $this->dm->detach($parent);
    }
}
