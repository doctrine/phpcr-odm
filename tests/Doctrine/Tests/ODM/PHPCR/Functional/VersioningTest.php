<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

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

        $user = $this->node->addNode('versionTestObj');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('_doctrine_alias', 'versionTestObj');
        $this->dm->getPhpcrSession()->save();
    }

    public function testCheckin()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkIn($user);
    }

    public function testCheckOut()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkIn($user);
        $this->dm->checkOut($user);
        $user->username = 'nicam';
        $this->dm->checkIn($user);
    }

    public function testRestore()
    {
        $user = $this->dm->find($this->type, '/functional/versionTestObj');
        $this->dm->checkIn($user);
        $this->dm->checkOut($user);
        $user->username = 'nicam';
        $this->dm->checkIn($user);

        $this->dm->restore('1.0', $user);
        $user = $this->dm->find($this->type, '/functional/versionTestObj');

        $this->assertEquals('lsmith', $user->username);
    }
}


/**
 * @Document(alias="versionTestObj")
 */
class VersionTestObj
{
    /** @Path */
    public $path;
    /** @Node */
    public $node;
    /** @String(name="username") */
    public $username;
    /** @Int(name="numbers", multivalue=true) */
    public $numbers;
}