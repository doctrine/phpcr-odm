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

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command to generate the official query builder reference.
 *
 * Note that the following code is rather obfuscated.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class DumpQueryBuilderReferenceCommand extends Command
{
    const QB_NS = 'Doctrine\\ODM\\PHPCR\\Query\\Builder';

    protected $formatString;

    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:qb:dump-reference')
            ->addArgument('search', InputArgument::OPTIONAL)
            ->setDescription('Generate the official query builder reference in RST format')
            ->setHelp(<<<HERE
This command generates the official query builder reference in RST format, you can optionally
pass a "search" parameter to limit the reference to only those nodes matching the given regex:

    <info>$ ./bin/phpcrodm doctrine:phpcr:qb:dump-reference And</info>

Use standard unix redirection to dump the reference to a file:

    <info>$ ./bin/phpcrodm doctrine:phpcr:qb:dump-reference > /path/to/phpcr-odm-documentation/en/reference/query-builder-reference.rst
HERE
);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (version_compare(PHP_VERSION, '5.4', '<')) {
            throw new \Exception('Dump reference needs at least PHP 5.4');
        }

        $search = $input->getArgument('search');

        $map = $this->buildMap();

        if ($search) {
            foreach (array_keys($map['nodeMap']) as $type) {
                if (!preg_match('&'.$search.'&', $type)) {
                    unset($map['nodeMap'][$type]);
                }
            }
        }

        $out = implode("\n", $this->formatMapRst($map));
        $output->writeln($out);
    }

    protected function formatMapRst($map)
    {
        $f = array(
            'humanize' => function ($string) {
                $string = str_replace('_', ' ', $string);
                return ucfirst($string);
            },
            'genRef' => function ($string, $prefix) {
                $ref = strtolower($string);
                return sprintf(':ref:`%s <qbref_%s_%s>`', $string, $prefix, $ref);
            },
            'genAnc' => function ($string, $prefix) {
                $ref = strtolower($string);
                return sprintf('.. _qbref_%s_%s:', $prefix, $ref);
            },
            'underline' => function ($string, $underChar = '=') {
                return str_repeat($underChar, strlen($string));
            },
            'formatDoc' => function ($string) {
                $out = array();
                $indent = 0;
                foreach (explode("\n", $string) as $line) {
                    if (strstr($line, '<code>')) {
                        $out[] = '.. code-block:: php';
                        $indent = 4;
                        $out[] = str_repeat(' ', $indent);
                        $out[] = str_repeat(' ', $indent).'<?php';
                    } elseif (strstr($line, '</code>')) {
                        $indent = 0;
                        $out[] = '';
                    } else {
                        $out[] = str_repeat(' ', $indent).$line;
                    }
                }

                return implode("\n", $out);
            }
        );

        $out = array();
        $out[] = 'Query Builder Reference';
        $out[] = '=======================';
        $out[] = '';
        $out[] = '.. note::';
        $out[] = '';
        $out[] = '    This document is generated by the PHPCR-ODM from the API, if you wish to contribute a fix please either';
        $out[] = '    create an issue or make a pull request on the phpcr-odm repository.';
        $out[] = '';
        $out[] = '    All the classes here documented can be found in the namespace: ``Doctrine\ODM\PHPCR\Query\Builder``';
        $out[] = '';

        $nti = array();
        $nti[] = 'Node Type Index';
        $nti[] = '---------------';
        $nti[] = '';

        foreach ($map['nodeTypeIndex'] as $nType => $nClasses) {
            $nti[] = $f['genAnc']($nType, 'type');
            $nti[] = '';
            $nti[] = $f['humanize']($nType);
            $nti[] = $f['underline']($nType, '~');;
            $nti[] = '';
            
            foreach ($nClasses as $nClass) {
                $nti[] = '* '.$f['genRef']($nClass, 'node');
            }
            $nti[] = '';
        }

        $out[] = 'Reference';
        $out[] = '---------';
        $out[] = '';

        foreach ($map['nodeMap'] as $nClass => $nData) {
            if ($nData['isLeaf']) {
                continue;
            }

            $out[] = $f['genAnc']($nClass, 'node');
            $out[] = '';
            $out[] = 'Node: '.$nClass;
            $out[] = '~~~~~~'.$f['underline']($nClass, '~');
            $out[] = '';
            if ($nData['doc']) {
                $out[] = $f['formatDoc']($nData['doc']);
                $out[] = '';
            }

            $out[] = '**Type**: '.$f['genRef']($nData['nodeType'], 'type');
            $out[] = '';

            if ($nData['parent']) {
                $out[] = '**Extends**: '.$f['genRef']($nData['parent'], 'node');
                $out[] = '';

                $inheritedMethodLinks = array();
                foreach ($nData['inheritedMethods'] as $inheritedMethod => $inheritedMethodClass) {
                    $inheritedMethodLinks[] = $f['genRef']($inheritedMethod, 'method_'.strtolower($inheritedMethodClass));
                }
                $out[] = '**Inherited methods**: '.implode(', ', $inheritedMethodLinks);
                $out[] = '';
            }

            if ($nData['cardMap']) {
                $out[] = '**Child Cardinality**:';
                // dump cardinality map
                foreach ($nData['cardMap'] as $cnType => $cnLimits) {
                    list($cMin, $cMax) = $cnLimits;
                    $out[] = sprintf('    * **%s..%s** %s',
                        $cMin, $cMax ? $cMax : '*', $f['genRef']($cnType, 'type')
                    );
                }
                $out[] = '';
            }

            $out[] = '';
            if ($nData['fMeths']) {
                foreach ($nData['fMeths'] as $fMeth => $fData) {
                    $out[] = $f['genAnc']($fMeth, 'method_'.strtolower($nClass));
                    $out[] = '';
                    $out[] = $fTitle = '->'.$fMeth;
                    $out[] = $f['underline']($fTitle, '^');
                    $out[] = '';
                    $out[] = $f['formatDoc']($fData['doc']);
                    $out[] = '';
                    $out[] = sprintf('**Adds**: %s (%s)',
                        $f['genRef']($fData['rNodeType'], 'node'),
                        $fData['fType']
                    );
                    $out[] = '';
                    $out[] = '**Returns**: '.$f['genRef']($fData['rType'], 'node');
                    $out[] = '';

                    if ($fData['args']) {
                        $out[] = '**Arguments**:';
                        $out[] = '';
                        foreach ($fData['args'] as $argName => $arg) {
                            $out[] = sprintf('* **$%s**: *%s* %s',
                                $argName, $arg['type'], $arg['doc']
                            );
                        }
                        $out[] = '';
                    }
                }
            }
        }

        return $out;
    }

    protected function buildMap()
    {
        $map = array();
        $nodeMap = array();
        $nodeTypeIndex = array();

        $dirHandle = opendir(__DIR__.'/../../../Query/Builder');

        while ($fname = readdir($dirHandle)) {
            $className = sprintf(
                '\\'.self::QB_NS.'\\%s',
                substr($fname, 0, -4)
            );

            if (!class_exists($className)) {
                continue;
            }

            $refl = new \ReflectionClass($className);

            if ($refl->isSubclassOf(self::QB_NS.'\\AbstractNode')) {
                if (!$refl->isInstantiable()) {
                    continue;
                }

                $inst = $refl->newInstanceWithoutConstructor();
                $fMethRetMap = $this->getFactoryMethodMap($refl);
                $fMethData = array();

                foreach ($fMethRetMap as $fMeth => $fmData) {
                    $fmReflMeth = $refl->getMethod($fMeth);
                    $fmFactoryType = $fmData['factoryType'];
                    $fmReturnType = $fmData['returnType'];

                    if ($fmReflMeth->class != $refl->name) {
                        continue;
                    }

                    $fMethRefl = new \ReflectionClass(self::QB_NS.'\\'.$fmReturnType);
                    $fMethInst = $fMethRefl->newInstanceWithoutConstructor();

                    $fFactoryRefl = new \ReflectionClass(self::QB_NS.'\\'.$fmFactoryType);
                    $fFactoryInst = $fFactoryRefl->newInstanceWithoutConstructor();
                    $fmNodeType = $fFactoryInst->getNodeType();

                    $fMethDoc = $this->parseDocComment($fmReflMeth->getDocComment(), 0);
                    $fParams = $this->parseDocParams($fmReflMeth);

                    $fMethData[$fMeth] = array(
                        'args' => $fParams,
                        'rType' => $fmReturnType,
                        'rNodeType' => $fmNodeType,
                        'fType' => $fmFactoryType,
                        'doc' => $fMethDoc,
                    );
                }

                $cardinalityMap = $inst->getCardinalityMap();
                $doc = $this->parseDocComment($refl->getDocComment(), 0);
                $isLeaf = $refl->isSubclassOf('Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode');

                $parentName = null;
                $inheritedMethods = array();

                if ($parentRefl = $refl->getParentClass()) {
                    if ($parentRefl->isInstantiable()) {
                        $parentName = $parentRefl->getShortName();

                        $parentInst = $parentRefl->newInstanceWithoutConstructor();
                        $parentMethods = $this->getFactoryMethodMap($parentRefl);
                        foreach (array_keys($parentMethods) as $parentMethod) {
                            $parentReflMeth = $parentRefl->getMethod($parentMethod);

                            if ($parentReflMeth->class != $refl->name) {
                                $inheritedMethods[$parentReflMeth->name] = $parentReflMeth->getDeclaringClass()->getShortName();
                            }
                        }
                    }
                }

                $nodeData = array(
                    'nodeType' => $inst->getNodeType(),
                    'parent' => $parentName,
                    'inheritedMethods' => $inheritedMethods,
                    'doc' => $doc,
                    'fMeths' => $fMethData,
                    'cardMap' => $cardinalityMap,
                    'isLeaf' => $isLeaf,
                );

                $nodeMap[$refl->getShortName()] = $nodeData;

                if (!isset($nodeTypeMap['nodeTypeMap'][$inst->getNodeType()])) {
                    $nodeTypeMap[$inst->getNodeType()] = array();
                }

                $nodeTypeIndex[$inst->getNodeType()][] = $refl->getShortName();
            }
        }

        ksort($nodeMap);

        $map['nodeMap'] = $nodeMap;
        $map['nodeTypeIndex'] = $nodeTypeIndex;

        return $map;
    }

    protected function parseDocParams($reflMethod)
    {
        $params = array();

        $docComment = $reflMethod->getDocComment();
        $reflParams = $reflMethod->getParameters();

        // parse @params
        $docParams = array();
        foreach (explode("\n", $docComment) as $line) {
            if (preg_match('&@param +([a-zA-Z]+) ?\$([a-zA-Z0-9_]+) +(.*)&', $line, $matches)) {
                $docParams[$matches[2]] = array('type' => $matches[1], 'doc' => $matches[3]);
            }
        }

        foreach ($reflParams as $reflParam) {
            if ($reflParam->name == 'void') {
                continue;
            }

            if (!isset($docParams[$reflParam->name])) {
                throw new \Exception(sprintf(
                    'Undocummented parameter "%s" in "%s" for method "%s"',
                    $reflParam->name, $reflMethod->class, $reflMethod->name
                ));
            }

            $params[$reflParam->name] = $docParams[$reflParam->name];
        }

        return $params;
    }

    protected function parseDocComment($comment, $indent = 0)
    {
        $out = array();
        foreach (explode("\n", $comment) as $line) {
            if (strstr($line, '/**')) {
                continue;
            }

            if (strstr($line, '*/')) {
                break;
            }

            if (strstr($line, '@')) {
                continue;
            }

            $line = preg_replace('& *\* ?&', '', $line);

            $out[] = $line = str_repeat(' ', $indent).$line;
        }

        return trim(implode("\n", $out));
    }

    protected function getFactoryMethodMap($refl)
    {
        $reflMethods = $refl->getMethods();
        $fMethods = array();

        foreach ($reflMethods as $rMethod) {
            $comment = $rMethod->getDocComment();
            if (preg_match('&@factoryMethod ([A-Za-z]+)&', $comment, $matches)) {
                $fMethods[$rMethod->name] = array(
                    'returnType' => null,
                    'factoryType' => null,
                );

                if (!isset($matches[1])) {
                    throw new \Exception(sprintf(
                        'Expected annotation for factoryMethod "%s" to declare a child type.',
                        $rMethod->name
                    ));
                }

                $factoryType = $matches[1];

                $fMethods[$rMethod->name]['factoryType'] = $factoryType;

                if (preg_match('&@return ([A-Za-z]+)&', $comment, $matches)) {
                    if (!isset($matches[1])) {
                        throw new \Exception(sprintf(
                            'Expected docblock for factoryMethod "%s" to declare a return type.',
                            $rMethod->name
                        ));
                    }

                    $returnType = $matches[1];
                    $fMethods[$rMethod->name]['returnType'] = $returnType;
                }
            }
        }

        return $fMethods;
    }
}
