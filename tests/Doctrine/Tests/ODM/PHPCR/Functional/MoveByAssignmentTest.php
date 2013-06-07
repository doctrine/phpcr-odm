<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

use PHPCR\PropertyType;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsTeamUser;

/**
 * @group functional
 */
class MoveByAssignmentTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $this->type = 'Doctrine\Tests\Models\CMS\CmsTeamUser';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('dbu');
        $user->setProperty('username', 'dbu');
        $user->setProperty('status', 'created');
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);

        $user = $this->node->addNode('team');
        $user->setProperty('username', 'team');
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);

        $this->dm->getPhpcrSession()->save();
    }

    public function testRenameByAssignment()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNotNull($user, 'User must exist');

        $user->nodename = 'davidbu';
        $user->status = 'moved';

        $this->dm->flush();

        $this->dm->clear();

        $user1 = $this->dm->find($this->type, '/functional/davidbu');
        $this->assertNotNull($user1, 'User must exist');
        $this->assertEquals('moved', $user->status);

        $user1 = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNull($user1, 'User must not exist');
    }

    public function testMoveByAssignment()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNotNull($user, 'User must exist');

        $team = $this->dm->find($this->type, '/functional/team');
        $this->assertNotNull($team, 'User must exist');

        $user->parent = $team;
        $user->status = 'moved';

        $this->dm->flush();

        $this->dm->clear();

        $user1 = $this->dm->find($this->type, '/functional/team/dbu');
        $this->assertNotNull($user1, 'User must exist');
        $this->assertEquals('moved', $user->status);

        $user1 = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNull($user1, 'User must not exist');
    }

    public function testMoveAndRenameByAssignment()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNotNull($user, 'User must exist');

        $team = $this->dm->find($this->type, '/functional/team');
        $this->assertNotNull($team, 'User must exist');

        $user->nodename = 'davidbu';
        $user->parent = $team;
        $user->status = 'moved';

        $this->dm->flush();

        $this->dm->clear();

        $user1 = $this->dm->find($this->type, '/functional/team/davidbu');
        $this->assertNotNull($user1, 'User must exist');
        $this->assertEquals('moved', $user->status);

        $user1 = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNull($user1, 'User must not exist');
    }

    public function testMoveByAssignmentWithProxy()
    {
        $user = $this->node->getNode('dbu')->addNode('assistant');
        $user->setProperty('username', 'foo');
        $user->setProperty('status', 'created');
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();

        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/dbu');
        $this->assertNotNull($user, 'User must exist');

        $team = $this->dm->find($this->type, '/functional/team');
        $this->assertNotNull($team, 'Team must exist');

        $user->parent = $team;

        $this->assertFalse($user->child->__isInitialized());

        $this->dm->flush();

        $this->assertFalse($user->child->__isInitialized());
        $this->assertEquals('/functional/team/dbu/assistant', $user->child->getId());
        $this->assertEquals('foo', $user->child->getUsername());
    }
}
