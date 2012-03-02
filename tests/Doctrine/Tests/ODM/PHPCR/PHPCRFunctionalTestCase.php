<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\DocumentManager;

abstract class PHPCRFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    private $_session;

    public function createDocumentManager(array $paths = null)
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->addGlobalIgnoredName('group');

        if (empty($paths)) {
            $paths = array(__DIR__ . "/../../Models");
        }

        $metaDriver = new AnnotationDriver($reader, $paths);

        $factoryclass = isset($GLOBALS['DOCTRINE_PHPCR_FACTORY'])
            ? $GLOBALS['DOCTRINE_PHPCR_FACTORY'] : '\Jackalope\RepositoryFactoryJackrabbit';

        if ($factoryclass === '\Jackalope\RepositoryFactoryDoctrineDBAL') {
            $params = array();
            foreach ($GLOBALS as $key => $value) {
                if (0 === strpos($key, 'jackalope.doctrine.dbal.')) {
                    $params[substr($key, strlen('jackalope.doctrine.dbal.'))] = $value;
                }
            }
            if (isset($params['username'])) {
                $params['user'] = $params['username'];
            }
            $GLOBALS['jackalope.doctrine_dbal_connection'] = \Doctrine\DBAL\DriverManager::getConnection($params);
        }

        $parameters = array_intersect_key($GLOBALS, $factoryclass::getConfigurationKeys());
        // factory will return null if it gets unknown parameters

        $repository = $factoryclass::getRepository($parameters);
        $this->assertNotNull($repository, 'There is an issue with your parameters: '.var_export(array_keys($parameters), true));

        $workspace = isset($GLOBALS['DOCTRINE_PHPCR_WORKSPACE'])
            ? $GLOBALS['DOCTRINE_PHPCR_WORKSPACE'] : 'tests';

        $user = isset($GLOBALS['DOCTRINE_PHPCR_USER'])
            ? $GLOBALS['DOCTRINE_PHPCR_USER'] : '';
        $pass = isset($GLOBALS['DOCTRINE_PHPCR_PASS'])
            ? $GLOBALS['DOCTRINE_PHPCR_PASS'] : '';

        $credentials = new \PHPCR\SimpleCredentials($user, $pass);
        $this->_session = $repository->login($credentials, $workspace);

        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($metaDriver);

        return DocumentManager::create($this->_session, $config);
    }

    public function resetFunctionalNode($dm)
    {
        $session = $dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }

        $node = $root->addNode('functional');
        $session->save();

        $dm->clear();

        return $node;
   }

    public function tearDown()
    {
        if ($this->_session) {
            $this->_session->logout();
        }
    }
}