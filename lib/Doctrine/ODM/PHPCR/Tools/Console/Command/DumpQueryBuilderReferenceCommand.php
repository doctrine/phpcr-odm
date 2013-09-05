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
use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode;
use Symfony\Component\Console\Input\InputOption;

/**
 * Dump a structure reference of the query builder.
 *
 * This is useful for writing documentation and potentially useful as 
 * a command line reference for the user. Although I would not be against
 * removing it completely if there are not sufficient use cases.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class DumpQueryBuilderReferenceCommand extends Command
{
    const QB_NS = 'Doctrine\\ODM\\PHPCR\\Query\\QueryBuilder';

    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:qb:dump-reference')
            ->addOption('depth', null, InputOption::VALUE_REQUIRED, 'Depth of reference dump', 3)
            ->setDescription('Splurge the structure of the query builder to stdOut');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nodeAssocMap = array();

        $dirHandle = opendir(__DIR__.'/../../../Query/QueryBuilder');
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

                $node = $refl->newInstanceWithoutConstructor();

                $fMethods = $node->getFactoryMethodMap();

                $nodeAssocMap[$refl->getShortName()] = array(
                    'factoryMap' => $fMethods,
                    'cardinalityMap' => $node->getCardinalityMap(),
                );
            }
        }

        $out = implode("\n", $this->formatTree($nodeAssocMap, 'Builder'));
        // $out = $this->formatRst($nodeAssocMap);
        $output->writeln($out);
    }

    protected function formatTree($nodeAssocMap, $type, $level = 0, $iterated = array())
    {
        if ($level > 2) {
            return array();
        }

        $out = array();
        $indent = str_repeat(' ', $level * 2);

        foreach ($nodeAssocMap[$type]['factoryMap'] as $method => $ret) {
            if ($ret == $type) {
                $out[] = $indent.'   <comment>-></comment> ** recursion **';
                continue;
            }

            $refl = new \ReflectionClass(self::QB_NS.'\\'.$ret);
            $inst = $refl->newInstanceWithoutConstructor();
            $nt = $inst->getNodeType();

            if (isset($nodeAssocMap[$type]['cardinalityMap'][$nt])) {
                list($cMin, $cMax) = $nodeAssocMap[$type]['cardinalityMap'][$nt];
            } else {
                $cMin = $cMax = '?';
            }
            $out[] = sprintf('%s<comment> -> </comment>%s() : <info>%s</info> [%s..%s]',
                $indent, $method, 
                $nt,
                $cMin, $cMax == null ? '*' : $cMax
            );

            if (!in_array($ret, $iterated)) {
                $iterated[] = $type;

                $out = array_merge(
                    $out,
                    $this->formatTree($nodeAssocMap, $ret, $level + 1, $iterated)
                );
            }

        }

        return $out;
    }

//    protected function formatRst($nodeAssocMap)
//    {
//        $out = '';
//        $out .= $this->buildRstBlock($nodeAssocMap, 'Builder', 'Builder Node');
//        $out .= $this->buildRstBlock($nodeAssocMap, 'ConstraintFactory', 'Constraint Node');
//        $out .= $this->buildRstBlock($nodeAssocMap, 'OperandDynamicFactory', 'Dynamic Operand Node');
//        $out .= $this->buildRstBlock($nodeAssocMap, 'OperandStaticFactory', 'Static Operand Node');
//        $out .= $this->buildRstBlock($nodeAssocMap, 'OrderBy', 'Order Node');
//
//        return $out;
//    }

//    protected function buildRstBlock($nodeAssocMap, $type, $label)
//    {
//        $out = array();
//        $out[] = $label;
//        $out[] = str_repeat('-', strlen($label));
//        $out[] = '';
//
//        foreach ($nodeAssocMap[$type]['cardinalityMap'] as $cType => $cardinalities) {
//            list($cMin, $cMax) = $cardinalities;
//            $out[] = sprintf('*[%s..%s]*: %s',
//                $cMin, $cMax === null ? '*' : $cMax,
//                $cType
//            );
//            $out[] = '';
//        }
//
//        foreach ($nodeAssocMap[$type]['factoryMap'] as $mName => $rType) {
//            if ($fMethods = $nodeAssocMap[$rType]['factoryMap']) {
//                // factory node
//                $out[] = $l = $mName.' (factory)';
//                $out[] = str_repeat('~', strlen($l));
//                $out[] = '';
//
//                $methods = array();
//                foreach (array_keys($fMethods) as $fMethod) {
//                    $methods[] = $fMethod.'()';
//                }
//                $out[] = 'Methods: '.implode(', ', $methods);
//                $out[] = '';
//
//            } else {
//                $refl = new \ReflectionClass(self::QB_NS.'\\'.$type);
//                $meth = $refl->getMethod($mName);
//                $args = $meth->getParameters();
//                $argNames = array();
//                foreach ($args as $arg) {
//                    $argNames[] = $arg->name;
//                }
//
//                $out[] = $l = $mName.' (leaf)';
//                $out[] = str_repeat('~', strlen($l));
//                $out[] = '';
//
//                $out[] = 'Arguments: ';
//                foreach ($argNames as $arg) {
//                    $out[] = ' - '.$arg;
//                }
//
//                $out[] = '';
//            }
//        }
//
//        $out[] = '';
//
//        return implode("\n", $out);
//    }

//    protected function formatDot($nodeAssocMap)
//    {
//        $out = array();
//        $out[] = 'digraph G {';
//        foreach ($nodeAssocMap as $nodeType => $maps) {
//            foreach ($maps['factoryMap'] as $mName => $rType) {
//                $out[$nodeType.$mName.$rType] = sprintf('  %s -> "%s()";',
//                    $nodeType, $mName
//                );
//            }
//        }
//        $out[] = '}';
//
//        return implode("\n", $out);
//    }
}
