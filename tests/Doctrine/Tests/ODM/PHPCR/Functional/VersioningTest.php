<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping as ODM;

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
 * @ODM\Document(alias="versionTestObj")
 */
class VersionTestObj
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Node */
    public $node;
    /**
     * @ var string
     * @ODM\Version(name="isVersionField") // Jakuza 
     */
    public $isVersionField;
    /** @ODM\String(name="username") */
    public $username;
    /** @ODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}
