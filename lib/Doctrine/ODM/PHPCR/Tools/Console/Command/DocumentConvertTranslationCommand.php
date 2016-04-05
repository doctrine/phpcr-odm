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

use Doctrine\ODM\PHPCR\Tools\Helper\TranslationConverter;
use PHPCR\SessionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Convert translated fields of a document class from an old translation format or untranslated.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class DocumentConvertTranslationCommand extends Command
{
    private $translationConverter;

    public function __construct($name = null, TranslationConverter $translationConverter = null)
    {
        parent::__construct($name);
        $this->translationConverter = $translationConverter;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:document:convert-translation')
            ->setDescription('Convert fields to translated or back to untranslated, and between different strategies after a refactoring.')

            ->addArgument('classname', InputArgument::REQUIRED, 'Class that has changed translation information')
            ->addOption('previous-strategy', 'prev', InputOption::VALUE_OPTIONAL,
                'Name of the previous translation strategy if there was one. Omit for converting from non-translated to translated',
                'none'
            )
            ->addOption('fields', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'The fields to convert. If not specified, all fields configured as translated will be converted.',
                array()
            )
            ->addOption('locales', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Locales to copy previously untranslated fields into.',
                array()
            )
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use to bypass the confirmation dialog')
            ->setHelp(<<<HERE
The <info>doctrine:phpcr:document:convert-translation</info> command migrates translations
from a previous format to the current mapping.

  <info>$ php ./app/console/phpcr doctrine:phpcr:document:convert-translation "Document\\ClassName"</info>

<comment>When some fields already where translated, you need to specify which fields to convert.</comment>
Failing to do that would erase all fields already translated previously.

Note that when only some fields changed or when converting between translation strategies, you need
to specify the previous strategy. When converting to untranslated, you additionally need to specify
the fields that previously where translated.
HERE
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('classname');
        $locales = $input->getOption('locales');
        $force = $input->getOption('force');
        if (!$force) {
            $force = $this->askConfirmation(
                $input,
                $output,
                sprintf('<question>Are you sure you want to migrate translations in %s Y/N ?</question>', $class),
                false
            );
        }

        if (!$force) {
            $output->writeln('<error>Aborted</error>');

            return 1;
        }

        /** @var $session SessionInterface */
        $session = $this->getHelper('phpcr')->getSession();
        $converter = $this->getTranslationConverter();
        $previous = $input->getOption('previous-strategy');
        $fields = $input->getOption('fields');

        do {
            $continue = $converter->convert($class, $locales, $fields, $previous);
            $notices = $converter->getLastNotices();
            if (count($notices)) {
                foreach ($notices as $path => $realClass) {
                    $output->writeln(sprintf(
                        'Document at %s is of class %s but requested to convert %s.',
                        $path,
                        $realClass,
                        $class
                    ));
                }
            }
            $session->save();
            $output->write('.');
        } while ($continue);

        $output->writeln('');
        $output->writeln('done');

        return 0;
    }

    /**
     * @return TranslationConverter
     */
    private function getTranslationConverter()
    {
        if (!$this->translationConverter) {
            $this->translationConverter = new TranslationConverter(
                $this->getHelper('phpcr')->getDocumentManager()
            );
        }

        return $this->translationConverter;
    }
    /**
     * Ask for confirmation with the question helper or the dialog helper for symfony < 2.5 compatibility.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $question
     * @param boolean         $default
     *
     * @return string
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default = true)
    {
        if ($this->getHelperSet()->has('question')) {
            $question = new ConfirmationQuestion($question, $default);

            return $this->getHelper('question')->ask($input, $output, $question);
        }

        return $this->getHelper('dialog')->askConfirmation($output, $question, $default);
    }
}
