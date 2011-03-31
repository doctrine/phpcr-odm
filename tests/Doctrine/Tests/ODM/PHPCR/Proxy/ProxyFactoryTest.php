<?php

namespace Doctrine\Tests\ODM\PHPCR\Proxy;

use Doctrine\ODM\PHPCR\Proxy\ProxyFactory;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;

/**
 * Test the proxy factory.
 * @author Nils Adermann <naderman@naderman.de>
 */
class ProxyFactoryTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRTestCase
{
    private $uowMock;
    private $dmMock;
    private $persisterMock;

    /**
     * @var \Doctrine\ODM\PHPCR\Proxy\ProxyFactory
     */
    private $proxyFactory;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        foreach (new \DirectoryIterator(__DIR__ . '/generated') as $file) {
            if (strstr($file->getFilename(), '.php')) {
                unlink($file->getPathname());
            }
        }
    }

    public function testReferenceProxyDelegatesLoadingToThePersister()
    {
        $proxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $modelClass = 'Doctrine\Tests\Models\ECommerce\ECommerceFeature';

        $query = array('documentName' => '\\'.$modelClass, 'id' => 'SomeUUID');

        $repositoryMock = $this->getMock('Doctrine\ODM\PHPCR\DocumentRepository', array('refresh'), array(), '', false);
        $repositoryMock->expects($this->atLeastOnce())
                      ->method('refresh')
                      ->with($this->isInstanceOf($proxyClass));

        $dmMock = new DocumentManagerMock();
        $dmMock->setRepositoryMock($repositoryMock);

        $this->proxyFactory = new ProxyFactory($dmMock, __DIR__ . '/generated', 'Proxies', true);

        $proxy = $this->proxyFactory->getProxy($modelClass, $query['id'], $query['documentName']);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Proxy\Proxy', $proxy);

        $proxy->getDescription();
    }
}

class DocumentManagerMock extends \Doctrine\ODM\PHPCR\DocumentManager
{
    private $repository;

    public function setRepositoryMock($mock)
    {
        $this->repository = $mock;
    }

    public function getRepository($documentName)
    {
        return $this->repository;
    }

    public function getClassMetadata($class)
    {
        return new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata($class);
    }

    public function getMetadataFactory()
    {
        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create();
        return new \Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory($dm);
    }

    public function getUnitOfWork()
    {
        return $this->uowMock;
    }
}
