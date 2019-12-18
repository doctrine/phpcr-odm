<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\Query\InvalidQueryException;
use Doctrine\ODM\PHPCR\Query\Query;
use PHPCR\Query\QueryInterface;

/**
 * @group functional
 */
class QuerySql2Test extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type = QuerySql2TestObj::class;

    /**
     * @var NodeInterface
     */
    private $node;

    public function queryStatements()
    {
        return array(
            array('SELECT username FROM [nt:unstructured] WHERE ISCHILDNODE("/functional")', 5),
            array('SELECT username FROM [nt:unstructured] WHERE ISCHILDNODE("/functional") ORDER BY username', 5),
            array('SELECT username FROM [nt:unstructured] WHERE ISCHILDNODE("/functional") AND username="dbu"', 1),
            array('SELECT username FROM [nt:unstructured] WHERE ISCHILDNODE("/functional") AND username="notexisting"', 0),
            array('invalidstatement', -1),
            // TODO: try a join
        );
    }

    public function queryRepositoryStatements()
    {
        return array(
            array('SELECT username FROM [nt:unstructured] AS a WHERE ISCHILDNODE(a, "/functional")', 4),
            array('SELECT username FROM [nt:unstructured] AS a WHERE ISCHILDNODE(a, "/functional") ORDER BY username', 4),
            array('SELECT username FROM [nt:unstructured] AS a WHERE ISCHILDNODE(a, "/functional") AND username="dbu"', 1),
            array('SELECT username FROM [nt:unstructured] AS a WHERE ISCHILDNODE(a, "/functional") AND username="notexisting"', 0),
            array('invalidstatement', -1),
            // TODO: try a join
        );
    }

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $versionNode = $this->node->addNode('node1', 'nt:unstructured');
        $versionNode->setProperty('username', 'dbu');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:class', $this->type);

        $versionNode = $this->node->addNode('node2', 'nt:unstructured');
        $versionNode->setProperty('username', 'johannes');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:class', $this->type);

        $versionNode = $this->node->addNode('node3', 'nt:unstructured');
        $versionNode->setProperty('username', 'lsmith');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:class', $this->type);

        $versionNode = $this->node->addNode('node4', 'nt:unstructured');
        $versionNode->setProperty('username', 'uwe');
        $versionNode->setProperty('numbers', array(3, 1, 2));
        $versionNode->setProperty('phpcr:class', $this->type);

        $versionNode = $this->node->addNode('node5', 'nt:unstructured');
        $versionNode->setProperty('numbers', array(3, 1, 2));

        $this->dm->getPhpcrSession()->save();
        $this->dm = $this->createDocumentManager();
    }

    /**
     * @dataProvider queryStatements
     */
    public function testQuery($statement, $rowCount)
    {
        if ($rowCount == -1) {
            // magic to tell this is an invalid query
            $this->expectException(InvalidQueryException::class);
        }
        $query = $this->dm->createQuery($statement, QueryInterface::JCR_SQL2);
        $this->assertInstanceOf(Query::class, $query);

        $result = $query->execute();
        $this->assertCount($rowCount, $result);
    }

    /**
     * @dataProvider queryRepositoryStatements
     */
    public function testRepositoryQuery($statement, $rowCount)
    {
        if ($rowCount == -1) {
            // magic to tell this is an invalid query
            $this->expectException(InvalidQueryException::class);
        }

        $repository = $this->dm->getRepository($this->type);
        $query = $repository->createQuery($statement, QueryInterface::JCR_SQL2);
        $this->assertInstanceOf(Query::class, $query);

        $result = $query->execute();
        $this->assertCount($rowCount, $result);
    }

    public function testQueryLimit()
    {
        $query = $this->dm->createPhpcrQuery('SELECT * FROM [nt:unstructured] WHERE ISCHILDNODE("/functional") ORDER BY username',
                                        QueryInterface::JCR_SQL2);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $query->setLimit(2);
        $result = $this->dm->getDocumentsByPhpcrQuery($query, $this->type);
        $this->assertCount(2, $result);
        $ids = array();
        $vals = array();
        $nums = array();
        foreach ($result as $obj) {
            $this->assertInstanceOf(QuerySql2TestObj::class, $obj);
            $ids[] = $obj->id;
            $vals[] = $obj->username;
            $nums[] = $obj->numbers;
        }
        $this->assertEquals(array('/functional/node5', '/functional/node1'), $ids);
        $this->assertEquals(array(null, 'dbu'), $vals);
        $this->assertEquals(array(array(3, 1, 2), array(3, 1, 2)), $nums);
    }
}

/**
 * @PHPCRODM\Document()
 */
class QuerySql2TestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Field(type="string") */
    public $username;
    /** @PHPCRODM\Field(type="long", multivalue=true) */
    public $numbers;
}
