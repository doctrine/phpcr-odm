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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPCR\Util\Console\Command\NodeTypeRegisterCommand;
use Doctrine\ODM\PHPCR\Translation\Translation;
use Doctrine\ODM\PHPCR\NodeTypeRegistrator;

/**
 * Command to register the phcpr-odm required node types.
 *
 * This command registers the necessary node types to get phpcr odm working
 */
class RegisterSystemNodeTypesCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('doctrine:phpcr:register-system-node-types');
        $this->setDescription('Register system node types in the PHPCR repository');
        $this->setHelp(<<<EOT
Register system node types in the PHPCR repository.

This command registers the node types necessary for the ODM to work.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $session \PHPCR\SessionInterface */
        $session = $this->getHelper('phpcr')->getSession();
        $registrator = new NodeTypeRegistrator();

        try {
            $registrator->registerNodeTypes($session);
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return 1;
        }

        $output->write(PHP_EOL.sprintf('Successfully registered system node types.') . PHP_EOL);

        return 0;
    }
}
