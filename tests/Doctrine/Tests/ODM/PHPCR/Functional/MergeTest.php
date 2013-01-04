<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsArticle;

class MergeTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getPhpcrSession()->save();
    }

    public function testMergeNewDocument()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $mergedUser = $this->dm->merge($user);

        $this->assertNotSame($mergedUser, $user);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $mergedUser);
        $this->assertEquals("beberlei", $mergedUser->username);
        $this->assertEquals($this->node->getPath().'/'.$mergedUser->username, $mergedUser->id, "Merged new document should have generated path");
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\ReferenceManyCollection', $mergedUser->articles);
    }

    public function testMergeManagedDocument()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->persist($user);
        $this->dm->flush();

        $mergedUser = $this->dm->merge($user);

        $this->assertSame($mergedUser, $user);
    }

    public function testMergeKnownDocument()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $mergedUser = $this->dm->merge($user);

        $this->assertNotSame($mergedUser, $user);
        $this->assertSame($mergedUser->id, $user->id);
    }

    public function testMergeRemovedDocument()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->remove($user);

        $this->setExpectedException('InvalidArgumentException', 'Removed document detected during merge. Can not merge with a removed document.');
        $this->dm->merge($user);
    }

    public function testMergeWithManagedDocument()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->persist($user);
        $this->dm->flush();

        $mergableUser = new CmsUser();
        $mergableUser->id = $user->id;
        $mergableUser->username = "jgalt";
        $mergableUser->name = "John Galt";

        $mergedUser = $this->dm->merge($mergableUser);

        $this->assertSame($mergedUser, $user);
        $this->assertEquals("jgalt", $mergedUser->username);
    }

    public function testMergeUnknownAssignedId()
    {
        $doc = new CmsArticle();
        $doc->id = "/foo";
        $doc->name = "Foo";

        $mergedDoc = $this->dm->merge($doc);

        $this->assertNotSame($mergedDoc, $doc);
        $this->assertSame($mergedDoc->id, $doc->id);
    }
}