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

namespace Doctrine\ODM\PHPCR\Tools\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\SessionInterface;

/**
 * Helper class to make DocumentManager available to console command
 */
class DocumentManagerHelper extends Helper
{
    protected $session;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * Constructor
     *
     * @param SessionInterface $session
     * @param DocumentManager  $dm
     */
    public function __construct(SessionInterface $session = null, DocumentManager $dm = null)
    {
        if (!$session && $dm) {
            $session = $dm->getPhpcrSession();
        }

        $this->session = $session;
        $this->dm = $dm;
    }

    public function getDocumentManager()
    {
        return $this->dm;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getName()
    {
        return 'phpcr';
    }
}
