<?php
/**
 * 2016 (C) BOdev Office | bodevoffice.com
 *
 * @license MIT
 *
 * Developed by Biber Ltd. (http://www.biberltd.com), a partner of BOdev Office (http://www.bodevoffice.com)
 *
 * Paid Customers ::
 *
 * Check http://team.bodevoffice.com for technical documentation or consult your representative.
 *
 * Contact support@bodevoffice.com for support requests.
 */

namespace BiberLtd\Bundle\Phorient\Odm\Entity;

use AppBundle\Entity\SaasAcademic\AcademicUnit;
use BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidRecordIdString;
use BiberLtd\Bundle\Phorient\Odm\Types\BaseType;
use BiberLtd\Bundle\Phorient\Odm\Types\ORecordId;
use BiberLtd\Bundle\Phorient\Services\ClassDataManipulator;
use BiberLtd\Bundle\Phorient\Services\ClassManager;
use BiberLtd\Bundle\Phorient\Services\Metadata;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\ORM\Mapping\Column;
use PhpOrient\Protocols\Binary\Data\ID as ID;
use PhpOrient\Protocols\Binary\Data\Record as ORecord;
use Doctrine\Common\Annotations\AnnotationReader as AnnotationReader;
use BiberLtd\Bundle\Phorient\Odm\Repository\BaseRepository;
use PhpOrient\Protocols\Binary\Data\Record;
use JMS\Serializer\Annotation as JMS;
class BaseClass
{
    /**
     * @ORM\Column(type="ORecordId")
     * @JMS\Accessor(getter="getStringId")
     * @JMS\Exclude(if="object.isNotRecordObject()")
     */
    private $rid = null;

    /**
     * @var bool
     * @JMS\Exclude()
     */
    private $modified = false;

    /**
     * @var string $version md5 Hash of object serialization
     * @JMS\Exclude()
     */
    protected $versionHash;

    /**
     * @var array Version history, the first element is always the original version
     * @JMS\Exclude()
     * @ORM\Column(type="OEmbeddedMap")
     */
    private $versionHistory = [];

    /**
     * @var array Holds definition of all public properties of a class for serialization purposes.
     * @JMS\Exclude()
     */
    private $updatedProps = [];

    /**
     * @JMS\Exclude()
     */
    public $dtFormat;
    /**
     * @var string
     * @JMS\Exclude()
     */
    private $typePath = 'BiberLtd\\Bundle\\Phorient\\Odm\\Types\\';

    protected $version = 0;

    private  $type='d';

    private $class;

    /**
     * BaseClass constructor.
     * @param string       $timezone
     */
    public function __construct($timezone = 'Europe/Istanbul')
    {
        $this->dtFormat = 'Y.m.d H:i:s';
    }

    /**
     * @return bool
     */
    public function isNotRecordObject()
    {
        if(!isset($this->rid) || is_null($this->rid)){
            return false;
        }
        return $this->rid->getValue()->cluster == -1 && $this->rid->getValue()->position == -1 ? true : false;
    }

    /**
     * @return bool|string
     */
    public function getStringId()
    {
        if(!isset($this->rid) || is_null($this->rid)){
            return null;
        }
        return '#'.$this->rid->getValue()->cluster.':'.$this->rid->getValue()->position;
    }

    /**
     * @return bool
     */
    final public function isModified()
    {
        $this->getUpdatedVersionHash();
        if($this->getUpdatedVersionHash() === $this->versionHash) {
            $this->modified = false;
            return false;
        }

        $this->modified = true;

        return true;
    }

    /**
     * @return $this
     */
    final public function setVersionHistory()
    {
        $dm = new ClassDataManipulator();
        if($this->versionHash !== $this->getUpdatedVersionHash() && !$this->modified) {
            $this->versionHistory[] = $dm->output($this,'json');
            $this->modified = true;
        } else {
            $this->modified = false;
        }

        return $this;
    }

    /**
     * @return string
     */
    final protected function getUpdatedVersionHash()
    {
        $dm = new ClassDataManipulator();
        return md5($dm->output($this,'json'));
    }

    /**
     * Alias to getRid() method
     *
     * @return ID
     */
    public function getRecordId($as = 'object')
    {
        return $this->getRid($as);
    }

    /**
     * @param string
     *
     * @return ID
     */
    public function getRid($as = 'object')
    {
        if($as == 'string') {
            if(is_null($this->rid)) return null;
            $id = $this->rid->getValue();

            return '#' . $id->cluster . ':' . $id->position;
        }

        return !is_null($this->rid) ? $this->rid->getValue() : null;
    }

    /**
     * Alias to setRid() method.
     *
     * @param $rid
     *
     * @return $this
     */
    public function setRecordId(ID $rid)
    {
        return $this->setRid($rid);
    }

    /**
     * @param $rid
     *
     * @return $this
     */
    public function setRid(ID $rid)
    {
        $this->rid = new ORecordId($rid);

        return $this;
    }

    /**
     * @return array
     */
    public function getUpdatedProps()
    {
        return $this->updatedProps;
    }

    /**
     * @param array $updatedProps
     */
    public function setUpdatedProps($updatedProps)
    {
        $this->updatedProps = $updatedProps;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }


}
