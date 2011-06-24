<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class VersioningTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\VersionTestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $versionNode = $this->node->addNode('versionTestObj');
        $versionNode->setProperty('username', 'lsmith');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:alias', 'versionTestObj');
        $versionNode->addMixin("mix:versionable");

        $this->dm->getPhpcrSession()->save();
        $this->dm = $this->createDocumentManager();
    }

    public function testCheckin()
    {
        $repository = $this->dm->getRepository($this->type);
        $user = $repository->find('/functional/versionTestObj');
        $this->dm->checkIn($user);
    }

    public function testCheckOut()
    {
        $repository = $this->dm->getRepository($this->type);
        $user = $repository->find('/functional/versionTestObj');
        $this->dm->checkIn($user);
        $this->dm->checkOut($user);
        $user->username = 'nicam';
        $this->dm->checkIn($user);
    }

    public function testRestore()
    {
        $repository = $this->dm->getRepository($this->type);
        $user = $repository->find('/functional/versionTestObj');
        $this->dm->checkIn($user);
        $this->dm->checkOut($user);
        $user->username = 'nicam';

        $this->dm->restore('1.0', $user);
        $user = $repository->find('/functional/versionTestObj');

        $this->assertEquals('lsmith', $user->username);
    }
}

/**
 * @PHPCRODM\Document(alias="versionTestObj")
 */
class VersionTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Version */
    public $isVersionField;
    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}
