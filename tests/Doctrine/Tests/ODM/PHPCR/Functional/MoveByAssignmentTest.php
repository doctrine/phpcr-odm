<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Tests\Models\CMS\CmsTeamUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

/**
 * @group functional
 */
class MoveByAssignmentTest extends PHPCRFunctionalTestCase
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
    private $type = CmsTeamUser::class;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
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

    public function testRenameByAssignment(): void
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

    public function testMoveByAssignment(): void
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

    public function testMoveAndRenameByAssignment(): void
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

    public function testMoveByAssignmentWithProxy(): void
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
