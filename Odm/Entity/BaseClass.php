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

use BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidRecordIdString;
use BiberLtd\Bundle\Phorient\Odm\Types\BaseType;
use BiberLtd\Bundle\Phorient\Odm\Types\ORecordId;
use BiberLtd\Bundle\Phorient\Services\ClassManager;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\ORM\Mapping\Column;
use PhpOrient\Protocols\Binary\Data\ID as ID;
use PhpOrient\Protocols\Binary\Data\Record as ORecord;
use Doctrine\Common\Annotations\AnnotationReader as AnnotationReader;
use BiberLtd\Bundle\Phorient\Odm\Repository\BaseRepository;
use PhpOrient\Protocols\Binary\Data\Record;

class BaseClass
{
    /**
     * @ORM\Column(type="ORecordId")
     */
    public $rid = null;

    /**
     * @var bool
     */
    protected $modified = false;

    /**
     * @var \DateTime
     */
    protected $dateAdded;

    /**
     * @var \DateTime
     */
    protected $dateUpdated;

    /**
     * @var \DateTime|null
     */
    protected $dateRemoved = null;

    /**
     * @var string $version md5 Hash of object serialization
     */
    protected $versionHash;

    /**
     * @var array Version history, the first element is always the original version
     */
    protected $versionHistory = [];

    /**
     * @var null|ORecord Stores the original Orient Record
     */
    protected $record;

    /**
     * @var array Holds definition of all public properties of a class for serialization purposes.
     */
    private $props = [];

    /**
     * @var array Holds annotation definitions.
     */
    private $propAnnotations = [];

    /**
     * @var ClassManager
     */
    protected $cm;

    /**
     * @var string
     */
    protected $typePath = 'BiberLtd\\Bundle\\Phorient\\Odm\\Types\\';

    /**
     * BaseClass constructor.
     *
     * @param ClassManager $cm
     * @param ORecord|null $record
     * @param string       $timezone
     */
    public function __construct(ClassManager $cm, ORecord $record = null, $timezone = 'Europe/Istanbul')
    {
        $this->cm = $cm;
        $this->prepareProps()->preparePropAnnotations();

        if(is_null($record)) {
            $this->dateAdded = new \DateTime('now', new \DateTimeZone($timezone));
            $this->record = $record;
            $this->dateUpdated = $this->dateAdded;
            $this->setDefaults();
        } else {
            $this->convertRecordToOdmObject($record);
        }

        $this->versionHash = md5(array_pop($this->versionHistory));
    }

    /**
     * @return bool
     */
    final public function isModified()
    {
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
        $this->versionHistory[] = $this->output('json');

        if($this->versionHash !== $this->getUpdatedVersionHash() && !$this->modified) {
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
        return md5($this->output('json'));
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
     * @param ORecord $record
     */
    public function convertRecordToOdmObject(ORecord $record)
    {
        $this->rid = new ORecordId($record->getRid());
        $recordData = $record->getOData();

        foreach($this->propAnnotations as $propName => $propAnnotations) {
            if($propName == 'rid') {
                continue;
            }

            foreach($propAnnotations as $propAnnotation) {
                if($propAnnotation instanceof Column) {
                    $set = 'set' . ucfirst($propName);
                    if(array_key_exists($propName, $recordData)) {
                        $this->$set($recordData[$propName]);
                    } else {
                        $this->$set(null);
                    }
                }
            }
        }
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return BaseClass
     * @throws InvalidRecordIdString
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $prefix = substr($name, 0, 3);
        $property = strtolower($name[3]) . substr($name, 4);

        if(!property_exists(get_class($this), $property)) {
            $property = strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $property));
        }

        switch($prefix) {
            case 'get':
                $colType = $this->typePath . $this->getColumnType($property);
                $onerow = false;

                switch($this->getColumnType($property)) {
                    case 'OLink':
                        $onerow = true;
                    case 'OLinkList':
                    case 'OLinkSet':
                    case 'OLinkMap':
                        if($this->ifHasLinkedClass($property)) {
                            return $this->$property instanceof BaseType ? $this->$property->getValue() : $this->$property;
                        } else {
                            return $this->$property instanceof BaseType ? $this->$property->getValue() : null;
                        }

                        break;
                    default:
                        return $this->ifHasLinkedClass($property) ? ($this->$property) : (is_object($this->$property) ? $this->$property->getValue() : null);
                }

                break;

            case 'set':

                if(count($arguments) != 1) {
                    throw new \Exception("Setter for $name requires exactly one parameter.");
                }

                $colType = $this->typePath . $this->getColumnType($property);
                $onerow = false;

                switch($this->getColumnType($property)) {
                    case 'OLink':
                        $onerow = true;
                    case 'OLinkList':
                    case 'OLinkSet':
                    case 'OLinkMap':
                        if($this->ifHasLinkedClass($property)) {
                            $linkedObj = $this->getNameSpace() . $this->getColumnOptions($property) ['class'];
                            $repoClass = $this->createRepository($this->getColumnOptions($property) ['class']);
                            $data = $onerow ? [ $arguments[0] ] : (is_null($arguments[0]) ? [] : $arguments[0]);
                            $obj = [];

                            foreach($data as $item) {
                                if($item != null) {
                                    if($item instanceof ID) {
                                        $response = $repoClass->selectByRid($item);
                                        if($response->result instanceof BaseClass) {
                                            $obj[] = $response->result;
                                        }

                                        if($response->result instanceof ORecord) {
                                            $obj[] = new $linkedObj($this->cm, $response->result);
                                        }
                                    } else {
                                        $obj[] = $item;
                                    }
                                } else {
                                    $obj[] = null;
                                }
                            }
                            $this->$property = $onerow ? new $colType($obj[0]) : new $colType($obj);
                        } else {
                            if(isset($arguments[0])) {
                                $data = $onerow ? [ $arguments[0] ] : $arguments[0];
                                $returnData = [];

                                foreach($data as $item) {
                                    if(!is_null($item) && !is_string($item)) {
                                        if($item instanceof BaseClass)
                                        {
                                            $item = $item->getRid();
                                        }
                                        if(!($item instanceof ID)) throw new InvalidRecordIdString();
                                    }

                                    if(is_string($item)) {
                                        $id = new ID($item);
                                        $returnData[] = $id;
                                    } else if($item instanceof ID) {
                                        $returnData[] = $item;
                                    }
                                }

                                $this->$property = $onerow ? new $colType($returnData[0]) : new $colType($returnData);
                            }
                        }
                        break;
                    case 'OEmbeddedMap':
                        $data = $arguments[0];
                        $data = is_array($data) ? json_decode(json_encode($data)) : [$data];
                        $newdata=[];
                        foreach($data as $index => $item)
                        {
                            $item = is_array($item) ? json_decode(json_encode($item)) : $item;
                            if(is_object($item))
                            {
                                $newdata[$index] = (property_exists($item,'oData')) ? ($item instanceof Record ? $item->getOdata() : $item->oData) : $item;
                            }else{
                                $newdata[$index] = $item;
                            }

                        }
                        $this->$property = new $colType($newdata);
                        break;
                    case 'OEmbeddedList':
                        if(is_array($arguments[0]))
                        {
                            $newdata=[];
                            foreach($arguments[0] as $item)
                            {
                                $item = is_array($item) ? json_decode(json_encode($item)) : $item;
                                $newdata[] = (property_exists($item,'oData')) ? ($item instanceof Record ? $item->getOdata() : $item->oData) : $item;
                            }
                            $this->$property = new $colType($newdata);
                        }else{
                            $this->$property = new $colType([]);
                        }

                        break;
                    case 'ODateTime':
                        if(!is_null($arguments[0])) {
                            $this->$property = new $colType($arguments[0] instanceof \DateTime ? $arguments[0] : \DateTime::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s',strtotime($arguments[0]))));
                        }
                        break;
                    default:
                        $this->$property = new $colType($arguments[0]);
                        break;
                }

                // Always return the value (Even on the set)
                return $this->setVersionHistory();
                break;

            default:
                throw new \Exception("Property $name doesn't exist.");
                break;
        }
    }

    /**
     * @param $entity
     *
     * @return mixed
     */
    private function createRepository($entity)
    {
        return $this->cm->getRepository($entity);
    }

    /**
     * @param $property
     *
     * @return bool
     */
    private function ifHasLinkedClass($property)
    {
        $options = $this->getColumnOptions($property);
        if(!is_array($options)) return false;
        if(!array_key_exists('class', $options)) return false;
        if(!array_key_exists('embedded', $options)) return false;
        if(!$options['embedded']) return false;
        return true;
    }

    /**
     * @return string
     */
    private function getNameSpace()
    {
        $reflectionClass = new \ReflectionClass($this);

        return $reflectionClass->getNamespaceName() . '\\';
    }

    /**
     * @return $this
     */
    final private function prepareProps()
    {
        $reflectionClass = new \ReflectionClass($this);
        $this->props = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        return $this;
    }

    /**
     * @return $this
     */
    final private function preparePropAnnotations()
    {
        $annoReader = new AnnotationReader();
        foreach($this->props as $aProperty) {
            $aPropertyReflection = new \ReflectionProperty(get_class($this), $aProperty->getName());
            $this->propAnnotations[$aProperty->getName()] = $annoReader->getPropertyAnnotations($aPropertyReflection);
        }

        return $this;
    }

    /**
     * @param $propertyName
     *
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function getColumnDefinition($propertyName)
    {
        $aPropertyReflection = new \ReflectionProperty(get_class($this), $propertyName);
        $annoReader = new AnnotationReader();
        $propAnnotations = $annoReader->getPropertyAnnotations($aPropertyReflection);

        foreach($propAnnotations as $aPropAnnotation) {
            if($aPropAnnotation instanceof Column) {
                return $aPropAnnotation;
            }
        }

        throw new AnnotationException();
    }

    /**
     * @param $propertyName
     *
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function getColumnType($propertyName)
    {
        $colDef = $this->getColumnDefinition($propertyName);

        return $colDef->type;
    }

    /**
     * @param $propertyName
     *
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function getColumnOptions($propertyName)
    {
        $colDef = $this->getColumnDefinition($propertyName);

        return isset($colDef->options) ? $colDef->options : false;
    }

    /**
     * @param string     $to
     * @param array|null $props
     *
     * @return string
     */
    public function output($to = 'json', array $props = null)
    {
        switch($to) {
            case 'json':
                return $this->outputToJson($props);
            case 'xml':
                return $this->outputToXml($props);
            case 'array':
                return json_decode($this->outputToJson($props), true);
        }
    }

    /**
     * @param array|null $props
     *
     * @return \stdClass
     */
    public function getRepObject(array $props = null)
    {
        $objRepresentation = new \stdClass();
        if(isset($this->controller->dateTimeFormat)) {
            $dtFormat = $this->controller->dateTimeFormat;
        } else {
            $dtFormat = 'd.m.Y H:i:s';
        }

        foreach($this->props as $aProperty) {
            $propName = $aProperty->getName();
            $propOptions = $this->getColumnOptions($propName);

            if(!is_null($props) && !in_array($propName, $props)) {
                continue;
            }

            if(!is_null($this->$propName)) {
                if(method_exists($this->$propName, 'getValue') && is_array($this->$propName->getValue())) {
                    $collection = [];

                    foreach($this->$propName->getValue() as $anItem) {
                        if($anItem instanceOf ID) {
                            $collection[] = '#' . $anItem->cluster . ':' . $anItem->position;
                        } else if($anItem instanceOf \DateTime) {
                            $collection[] = $anItem->format($dtFormat);
                        } else if(is_object($anItem) && method_exists($anItem, 'getValue')) {
                            $collection[] = $anItem->getValue();
                        } else {
                            $collection[] = $anItem;
                        }
                    }

                    $objRepresentation->$propName = $collection;
                } else if(method_exists($this->$propName, 'getValue') && $this->$propName->getValue() instanceOf \DateTime) {

                    $objRepresentation->$propName = $this->$propName->getValue()->format($dtFormat);
                } else if(method_exists($this->$propName, 'getValue') && $this->$propName->getValue() instanceOf ID) {
                    if($this->getColumnType($propName) == 'OLink' && isset($propOptions['embedded']) && $propOptions['embedded'] == true) {
                        $objRepresentation->$propName = $this->$propName->getValue(true)->getRepObject($props);
                    } else {
                        $idObj = $this->$propName->getValue();
                        $objRepresentation->$propName = '#' . $idObj->cluster . ':' . $idObj->position;
                    }
                } elseif(method_exists($this->$propName, 'getValue')) {
                    $propType = gettype($this->$propName->getValue());
                    $propObj = $this->$propName->getValue();

                    if($propType == 'object' && method_exists($propObj, 'getRepObject')) {
                        $objRepresentation->$propName = $this->$propName->getValue()->getRepObject($props);
                    } else if($propType == 'object' && !method_exists($propObj, 'getRepObject')) {
                        $objRepresentation->$propName = json_decode(json_encode($this->$propName->getValue()));
                    } else {
                        $propType = $this->getColumnType($propName);
                        $value = in_array($propType, [ 'OEmbeddedList', 'OLinkList', 'OEmbeddedSet' ]) && ($this->$propName->getValue() == null || empty($this->$propName->getValue())) ? [] : $this->$propName->getValue();
                        $objRepresentation->$propName = $value;
                    }
                }
            } else {
                $propType = $this->getColumnType($propName);
                if(in_array($propType, [ 'OEmbeddedList', 'OLinkList', 'OEmbeddedSet' ])) {
                    $value = [];
                }else{
                    $value = null;
                }

                $objRepresentation->$propName = $value;
            }
        }

        return $objRepresentation;
    }

    /**
     * @param array $props
     *
     * @return string
     */
    final private function outputToJson(array $props = null)
    {
        return json_encode($this->getRepObject($props));
    }

    /**
     * @param array $props
     *
     * @return string
     *
     * @todo !! BE AWARE !! xmlrpc_encode is an experimental method.
     */
    final private function outputToXml(array $props = null)
    {
        return xmlrpc_encode($this->getRepObject($props));
    }

    /**
     * @return array
     */
    final public function getProps()
    {
        return $this->props;
    }

    /**
     * @param string|null $nsRoot
     *
     * @return $this
     */
    private function setDefaults(string $nsRoot = null)
    {
        $nsRoot = $nsRoot ?? $this->typePath;

        foreach($this->props as $aProperty) {
            $propName = $aProperty->getName();
            $colType = $this->getColumnType($propName);
            $class = $nsRoot . $colType;
            $this->$propName = new $class();
        }

        return $this;
    }
}
