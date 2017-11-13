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
    public $rid = null;

    /**
     * @var bool
     * @JMS\Exclude()
     */
    protected $modified = false;

    /**
     * @var \DateTime
     * @JMS\Exclude()
     * @JMS\Type("DateTime<'d.m.Y H:i:s'>")
     */
    protected $dateAdded;

    /**
     * @var \DateTime
     * @JMS\Exclude(if="object.isNotRecordObject()")
     * @JMS\Type("DateTime<'d.m.Y H:i:s'>")
     */
    protected $dateUpdated;

    /**
     * @var \DateTime|null
     * @JMS\Exclude()
     * @JMS\Type("DateTime<'d.m.Y H:i:s'>")
     */
    protected $dateRemoved = null;

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
    protected $typePath = 'BiberLtd\\Bundle\\Phorient\\Odm\\Types\\';

    /**
     * BaseClass constructor.
     * @param string       $timezone
     */
    public function __construct($timezone = 'Europe/Istanbul')
    {
        $this->dtFormat = 'Y.m.d H:i:s';
    }

    public function isNotRecordObject()
    {
        return $this->rid->getValue()->cluster == -1 && $this->rid->getValue()->position == -1 ? true : false;
    }
    public function getStringId()
    {
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
        if($this->versionHash !== $this->getUpdatedVersionHash() && !$this->modified) {
            $this->versionHistory[] = $this->cm->getDataManipulator()->output($this,'json');
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
        return md5($this->cm->getDataManipulator()->output($this,'json'));
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
            if(is_null($this->rid->getValue())) return null;
            $id = $this->rid->getValue();

            return '#' . $id->cluster . ':' . $id->position;
        }

        return $this->rid->getValue();
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

}