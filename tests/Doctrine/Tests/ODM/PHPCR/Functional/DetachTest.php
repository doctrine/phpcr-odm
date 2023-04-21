<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
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
    private $type = CmsUser::class;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $node = $this->resetFunctionalNode($this->dm);

        $user = $node->addNode('lsmith');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', [3, 1, 2]);
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testDetachNewObject(): void
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

    public function testDetachedKnownObject(): void
    {
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->detach($user);

        $this->expectException(InvalidArgumentException::class);
        $this->dm->persist($user);
    }

    public function testDetach(): void
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $newUser = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertEquals('lsmith', $newUser->username);
    }

    public function testDetachWithPerist(): void
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);

        $this->expectException(InvalidArgumentException::class);
        $this->dm->persist($user);
    }

    public function testDetachWithMove(): void
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);

        $this->expectException(InvalidArgumentException::class);
        $this->dm->move($user, '/functional/user2');
    }

    public function testDetachWithRemove(): void
    {
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $user->username = 'new-name';

        $this->dm->detach($user);

        $this->expectException(InvalidArgumentException::class);
        $this->dm->remove($user);
    }

    public function testDetachWithChildren(): void
    {
        $parent = $this->dm->find(null, '/functional');

        $this->dm->detach($parent);
    }
}
