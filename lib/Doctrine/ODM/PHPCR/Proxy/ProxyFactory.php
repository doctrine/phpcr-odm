<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Proxy;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;

/**
 * This factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Nils Adermann <naderman@naderman.de>
 * @author Johannes Stark <starkj@gmx.de>
 * @author David Buchmann <david@liip.ch>
 */
class ProxyFactory
{
    /** The DocumentManager this factory is bound to. */
    private $dm;
    /** Whether to automatically (re)generate proxy classes. */
    private $autoGenerate;
    /** The namespace that contains all proxy classes. */
    private $proxyNamespace;
    /** The directory that contains all proxy classes. */
    private $proxyDir;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>DocumentManager</tt>.
     *
     * @param DocumentManager $dm           The DocumentManager the new factory works for.
     * @param string          $proxyDir     The directory to use for the proxy classes. It must exist.
     * @param string          $proxyNs      The namespace to use for the proxy classes.
     * @param boolean         $autoGenerate Whether to automatically generate proxy classes.
     */
    public function __construct(DocumentManager $dm, $proxyDir, $proxyNs, $autoGenerate = false)
    {
        if (!$proxyDir) {
            throw ProxyException::proxyDirectoryRequired();
        }
        if (!$proxyNs) {
            throw ProxyException::proxyNamespaceRequired();
        }
        $this->dm = $dm;
        $this->proxyDir = $proxyDir;
        $this->autoGenerate = $autoGenerate;
        $this->proxyNamespace = $proxyNs;
    }

    /**
     * Gets a reference proxy instance for the entity of the given type and identified by
     * the given identifier.
     *
     * @param string $className
     * @param mixed  $identifier
     *
     * @return object
     */
    public function getProxy($className, $identifier)
    {
        $fqn = ClassUtils::generateProxyClassName($className, $this->proxyNamespace);

        if (!class_exists($fqn, false)) {
            $fileName = $this->getProxyFileName($className);
            if ($this->autoGenerate) {
                $this->generateProxyClass($this->dm->getClassMetadata($className), $fileName, self::$proxyClassTemplate);
            }
            require $fileName;
        }

        if (!$this->dm->getMetadataFactory()->hasMetadataFor($fqn)) {
            $this->dm->getMetadataFactory()->setMetadataFor($fqn, $this->dm->getClassMetadata($className));
        }

        return new $fqn($this->dm, $identifier);
    }

    /**
     * Generate the Proxy file name
     *
     * @param string $className
     *
     * @return string
     */
    private function getProxyFileName($className, $toDir = null)
    {
        $proxyDir = $toDir ?: $this->proxyDir;
        $proxyDir = rtrim($proxyDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $proxyDir . DIRECTORY_SEPARATOR . '__CG__' . str_replace('\\', '', $className) . '.php';
    }

    /**
     * Generates proxy classes for all given classes.
     *
     * @param array  $classes The classes (ClassMetadata instances) for which to generate proxies.
     * @param string $toDir   The target directory of the proxy classes. If not specified, the
     *                      directory configured on the Configuration of the DocumentManager used
     *                      by this factory is used.
     */
    public function generateProxyClasses(array $classes, $toDir = null)
    {
        foreach ($classes as $class) {
            /* @var $class ClassMetadata */
            if ($class->isMappedSuperclass || $class->reflClass->isAbstract()) {
                continue;
            }

            $proxyFileName = $this->getProxyFileName($class->name, $toDir);
            $this->generateProxyClass($class, $proxyFileName, self::$proxyClassTemplate);
        }
    }

    /**
     * Generates a proxy class file.
     *
     * @param ClassMetadata $class         The class metadata
     * @param string        $proxyFileName path to the proxy file to generate
     * @param string        $template      The path of the template template.
     */
    private function generateProxyClass(ClassMetadata $class, $proxyFileName, $template)
    {
        $methods = $this->generateMethods($class);
        $attributes = $this->getUnsetAttributes($class);
        if (empty($attributes)) {
            $unsetAttributes = $unsetAttributesList = '';
        } else {
            $unsetAttributes = 'unset($this->'.implode(', $this->', $attributes).');';
            $unsetAttributesList = var_export($attributes, true);
        }

        $sleepImpl = $this->generateSleep($class);

        $placeholders = array(
            '<namespace>',
            '<proxyClassName>', '<className>',
            '<unsetattributes>',
            '<unsetattributesList>',
            '<methods>', '<sleepImpl>'
        );

        $className = rtrim($class->name, '\\');
        $proxyClassName = ClassUtils::generateProxyClassName($class->name, $this->proxyNamespace);
        $parts = explode('\\', strrev($proxyClassName), 2);
        $proxyClassNamespace = strrev($parts[1]);
        $proxyClassName = strrev($parts[0]);

        $replacements = array(
            $proxyClassNamespace,
            $proxyClassName,
            $className,
            $unsetAttributes,
            $unsetAttributesList,
            $methods,
            $sleepImpl
        );

        $compiled = str_replace($placeholders, $replacements, $template);

        file_put_contents($proxyFileName, $compiled, LOCK_EX);
    }

    /**
     * Get the attributes of the document class
     *
     * @param ClassMetadata $class
     *
     * @return array         List of attributes to unset
     */
    private function getUnsetAttributes(ClassMetadata $class)
    {
        return array_keys($class->mappings);
    }

    /**
     * Generates the methods of a proxy class.
     *
     * @param ClassMetadata $class
     *
     * @return string The code of the generated methods.
     */
    private function generateMethods(ClassMetadata $class)
    {
        $methods = '';

        foreach ($class->reflClass->getMethods() as $method) {
            /* @var $method \ReflectionMethod */
            if ($method->isConstructor() || strtolower($method->getName()) == '__sleep') {
                continue;
            }

            if ($method->isPublic() && !$method->isFinal() && !$method->isStatic()) {
                $methods .= PHP_EOL . '    public function ';
                if ($method->returnsReference()) {
                    $methods .= '&';
                }
                $methods .= $method->getName() . '(';
                $firstParam = true;
                $parameterString = $argumentString = '';

                foreach ($method->getParameters() as $param) {
                    /** @var $param \ReflectionParameter */
                    if ($firstParam) {
                        $firstParam = false;
                    } else {
                        $parameterString .= ', ';
                        $argumentString  .= ', ';
                    }

                    // We need to pick the type hint class too
                    if (($paramClass = $param->getClass()) !== null) {
                        $parameterString .= '\\' . $paramClass->getName() . ' ';
                    } elseif ($param->isArray()) {
                        $parameterString .= 'array ';
                    }

                    if ($param->isPassedByReference()) {
                        $parameterString .= '&';
                    }

                    $parameterString .= '$' . $param->getName();
                    $argumentString  .= '$' . $param->getName();

                    if ($param->isDefaultValueAvailable()) {
                        $parameterString .= ' = ' . var_export($param->getDefaultValue(), true);
                    }
                }

                $methods .= $parameterString . ')';
                $methods .= PHP_EOL . '    {' . PHP_EOL;
                $methods .= '        $this->__load();' . PHP_EOL;
                $methods .= '        return parent::' . $method->getName() . '(' . $argumentString . ');';
                $methods .= PHP_EOL . '    }' . PHP_EOL;
            }
        }

        return $methods;
    }

    /**
     * Generates the code for the __sleep method for a proxy class.
     *
     * @param ClassMetadata $class
     *
     * @return string
     */
    private function generateSleep(ClassMetadata $class)
    {
        $sleepImpl = '';

        if ($class->reflClass->hasMethod('__sleep')) {
            $sleepImpl .= "return array_merge(array('__isInitialized__'), parent::__sleep());";
        } else {
            $sleepImpl .= "return array('__isInitialized__', ";

            $properties = array();
            foreach ($class->fieldMappings as $fieldName) {
                $properties[] = "'$fieldName'";
            }

            $sleepImpl .= implode(',', $properties) . ');';
        }

        return $sleepImpl;
    }

    /** Proxy class code template */
    private static $proxyClassTemplate = <<<'PHP'
<?php

namespace <namespace>;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE PHPCR-ODM. DO NOT EDIT THIS FILE.
 */
class <proxyClassName> extends \<className> implements \Doctrine\ODM\PHPCR\Proxy\Proxy
{
    private $__doctrineDocumentManager__;
    private $__isInitialized__ = false;
    private $__identifier__;
    private $__unsetAttributes__;

    public function __construct($documentManager, $identifier)
    {
        <unsetattributes>
        $this->__unsetAttributes__ = <unsetattributesList>;
        $this->__doctrineDocumentManager__ = $documentManager;
        $this->__identifier__ = $identifier;
    }

    public function __load()
    {
        if (!$this->__isInitialized__ && $this->__doctrineDocumentManager__) {
            $this->__isInitialized__ = true;
            $this->__doctrineDocumentManager__->getRepository(get_class($this))->refreshDocumentForProxy($this);
            unset($this->__doctrineDocumentManager__);
        }
    }
    <methods>
    public function __sleep()
    {
        <sleepImpl>
    }

    public function __getIdentifier()
    {
        return $this->__identifier__;
    }

    public function __setIdentifier($identifier)
    {
        $this->__identifier__ = $identifier;
    }

    public function __isset($name)
    {
        return in_array($name, $this->__unsetAttributes__);
    }

    public function __set($name, $value)
    {
        $this->__load();
        $this->$name = $value;
    }

    public function &__get($name)
    {
        $this->__load();

        return $this->$name;
    }

    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }
}
PHP;
}
