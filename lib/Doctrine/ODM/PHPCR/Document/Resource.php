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

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a jcr nt:resource and is used by the File document
 * @see http://wiki.apache.org/jackrabbit/nt:resource
 *
 * @PHPCRODM\Document(nodeType="nt:resource")
 */
class Resource
{
    /** @PHPCRODM\Id */
    protected $id;

    /** @PHPCRODM\Node */
    protected $node;

    /** @PHPCRODM\Nodename */
    protected $nodename;

    /** @PHPCRODM\ParentDocument */
    protected $parent;

    /** @PHPCRODM\Binary(name="jcr:data") */
    protected $data;

    /** @PHPCRODM\String(name="jcr:mimeType") */
    protected $mimeType = 'application/octet-stream';

    /** @PHPCRODM\String(name="jcr:encoding") */
    protected $encoding;

    /** @PHPCRODM\Date(name="jcr:lastModified") */
    protected $lastModified;

    /** @PHPCRODM\String(name="jcr:lastModifiedBy") */
    protected $lastModifiedBy;

    /**
     * The node name of the file.
     *
     * @return string
     */
    public function getNodename()
    {
        return $this->nodename;
    }

    /**
     * Set the node name of the file. (only mutable on new document before the persist)
     *
     * @param string $name the name of the file
     */
    public function setNodename($name)
    {
        $this->nodename = $name;
    }

    /**
     * The parent File document of this Resource document.
     *
     * @return object File document that is the parent of this node.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent document of this document. Only mutable on new document
     * before the persist.
     *
     * @param object $parent Document that is the parent of this node.
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * setter for the data property
     * This property stores the content of this resource
     *
     * @param stream $data the contents of this resource
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * getter for the data property
     * This returns the content of this resource
     *
     * @param stream
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * setter for the mimeType property
     * This property stores the mimeType of this resource
     *
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     * getter for the mimeType property
     * This returns the mimeType of this resource
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * setter for the encoding property
     * This property stores the encoding of this resource
     *
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * getter for the encoding property
     * This returns the encoding of this resource
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * setter for the lastModified property
     * This property stores the lastModified date of this resource
     * If not set, this might be set by PHPCR
     *
     * @param \DateTime $lastModified
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * getter for the lastModified property
     * This returns the lastModified date of this resource
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * setter for the lastModifiedBy property
     * name of the jcr user that last modified this resource
     *
     * @param string $lastModifiedBy
     */
    public function setLastModifiedBy($lastModifiedBy)
    {
        $this->lastModifiedBy = $lastModifiedBy;
    }

    /**
     * getter for the lastModifiedBy property
     * This returns name of the jcr user that last modified this resource
     *
     * @return string
     */
    public function getLastModifiedBy()
    {
        return $this->lastModifiedBy;
    }

    /**
     * get mime and encoding (RFC2045)
     * @return string
     */
    public function getMime()
    {
        return $this->getMimeType() . ($this->getEncoding() ? '; charset=' . $this->getEncoding() : '');
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->nodename;
    }
}
