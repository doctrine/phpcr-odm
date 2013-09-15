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
    const QB_NS = 'Doctrine\\ODM\\PHPCR\\Query\\Builder';

    protected $formatString;

    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:qb:dump-reference')
            ->addArgument('search', InputArgument::OPTIONAL)
            ->addOption('format-rst', null, InputOption::VALUE_NONE)
            ->setDescription('Splurge the structure of the query builder to stdOut');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('blue');
        $output->getFormatter()->setStyle('blue', $style);
        $style = new OutputFormatterStyle('green', null, array('bold'));
        $output->getFormatter()->setStyle('keyword', $style);
        $style = new OutputFormatterStyle('magenta', null, array('bold'));
        $output->getFormatter()->setStyle('class', $style);

        $formatRst = $input->getOption('format-rst');

        $search = $input->getArgument('search');

        $map = $this->buildMap();

        if ($search) {
            foreach (array_keys($map['nodeMap']) as $type) {
                if (!preg_match('&'.$search.'&', $type)) {
                    unset($map['nodeMap'][$type]);
                }
            }
        }

        if ($map) {
            if ($formatRst) {
                $out = implode("\n", $this->formatMapRst($map));
            } else {
                $out = implode("\n", $this->formatMap($map));
            }
        } else {
            $output->writeln('<info>Nothing found for search </info>'.$search);
            return 0;
        }

        $output->writeln($out);
    }

    protected function formatMap($map)
    {
        $out = array();

        foreach ($map['nodeMap'] as $nClass => $nData) {
            if ($nData['parent']) {
                $out[] = sprintf('<class>%s</class> (%s) <keyword>extends</keyword> <class>%s</class>', $nClass, $nData['nodeType'], $nData['parent']);
            } else {
                $out[] = sprintf('<class>%s</class> (%s)', $nClass, $nData['nodeType']);
            }
            $out[] = $nData['doc'];

            // dump cardinality map
            foreach ($nData['cardMap'] as $cnType => $cnLimits) {
                list($cMin, $cMax) = $cnLimits;
                $out[] = sprintf('  [%s..%s] <blue>%s</blue>',
                    $cMin, $cMax ? $cMax : '*', $cnType
                );
            }


            foreach ($nData['fMeths'] as $fMeth => $fData) {
                $out[] = sprintf('  -><info>%s</info>(<comment>%s</comment>) : <class>%s</class> (<blue>%s</blue>)',
                    $fMeth,
                    implode(', ', $fData['args']),
                    $fData['rType'],
                    $fData['rNodeType']
                );
            }
        }

        return $out;
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
        $out[] = '    This is document is generated by the PHPCR-ODM';
        $out[] = '';
        $out[] = 'Node Type Index';
        $out[] = '---------------';
        $out[] = '';

        foreach ($map['nodeTypeIndex'] as $nType => $nClasses) {
            $out[] = $f['genAnc']($nType, 'type');
            $out[] = '';
            $out[] = $f['humanize']($nType);
            $out[] = $f['underline']($nType, '~');;
            $out[] = '';
            
            foreach ($nClasses as $nClass) {
                $out[] = '* '.$f['genRef']($nClass, 'node');
            }
            $out[] = '';
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
            $out[] = $nClass;
            $out[] = $f['underline']($nClass, '~');
            $out[] = '';
            if ($nData['doc']) {
                $out[] = $nData['doc'];
                $out[] = '';
            }

            $out[] = '* **Type**: '.$f['genRef']($nData['nodeType'], 'type');

            if ($nData['parent']) {
                $out[] = '* **Extends**: '.$f['genRef']($nData['parent'], 'node');
            }

            if ($nData['cardMap']) {
                $out[] = '* **Children**:';
                // dump cardinality map
                foreach ($nData['cardMap'] as $cnType => $cnLimits) {
                    list($cMin, $cMax) = $cnLimits;
                    $out[] = sprintf('    * **%s..%s** %s',
                        $cMin, $cMax ? $cMax : '*', $f['genRef']($cnType, 'type')
                    );
                }
            }

            $out[] = '';
            if ($nData['fMeths']) {
                foreach ($nData['fMeths'] as $fMeth => $fData) {
                    $out[] = $fTitle = '->'.$fMeth;
                    $out[] = $f['underline']($fTitle, '^');
                    $out[] = '';
                    if ($fData['args']) {
                        $out[] = 'Arguments:';
                        $out[] = '';
                        foreach ($fData['args'] as $arg) {
                            $out[] = '* **'.$arg.'**: Description of arg.';
                        }
                        $out[] = '';
                    }

                    $out[] = $f['formatDoc']($fData['doc']);
                    $out[] = '';
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
                $fMethRetMap = $inst->getFactoryMethodMap();
                $fMethData = array();

                foreach ($fMethRetMap as $fMeth => $fmType) {
                    $fmReflMeth = $refl->getMethod($fMeth);

                    if ($fmReflMeth->class != $refl->name) {
                        continue;
                    }

                    $fmArgs = array();
                    foreach ($fmReflMeth->getParameters() as $fmArg) {
                        if ($fmArg->name != 'void') { // hmm
                            $fmArgs[] = '$'.$fmArg->name;
                        }
                    }

                    $fMethRefl = new \ReflectionClass(self::QB_NS.'\\'.$fmType);
                    $fMethInst = $fMethRefl->newInstanceWithoutConstructor();
                    $fMethDoc = $this->parseDocComment($fmReflMeth->getDocComment(), 4);

                    $fMethData[$fMeth] = array(
                        'args' => $fmArgs,
                        'rType' => $fmType,
                        'rNodeType' => $fMethInst->getNodeType(),
                        'doc' => $fMethDoc,
                    );


                }

                $cardinalityMap = $inst->getCardinalityMap();
                $doc = $this->parseDocComment($refl->getDocComment(), 2);
                $isLeaf = $refl->isSubclassOf('Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode');

                $parentName = null;
                if ($parentRefl = $refl->getParentClass()) {
                    if ($parentRefl->isInstantiable()) {
                        $parentName = $parentRefl->getShortName();
                    }
                }

                $nodeData = array(
                    'nodeType' => $inst->getNodeType(),
                    'parent' => $parentName,
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

        $map['nodeMap'] = $nodeMap;
        $map['nodeTypeIndex'] = $nodeTypeIndex;

        return $map;
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

            $out[] = str_repeat(' ', $indent).$line;
        }

        return str_repeat(' ', $indent).trim(implode("\n", $out));
    }
}
