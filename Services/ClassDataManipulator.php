<?php
/**
 * Created by BoDev Office.
 * User: Erman Titiz ( @ermantitiz )
 * Date: 25/10/2017
 * Time: 15:19
 */

namespace BiberLtd\Bundle\Phorient\Services;
use BiberLtd\Bundle\Phorient\Odm\Entity\BaseClass;
use BiberLtd\Bundle\Phorient\Odm\Types\BaseType;
use BiberLtd\Bundle\Phorient\Odm\Types\ORecordId;
use PhpOrient\Protocols\Binary\Data\Record;

class ClassDataManipulator
{

    private $ignored = array('index', 'parent','modified','versionHash', 'typePath', 'updatedProps', 'dateAdded', 'dateRemoved', 'versionHistory','dtFormat');

    private $cm;

    /**
     * ClassDataManipulator constructor.
     * @param $cm
     */
    public function __construct($cm)
    {
        $this->cm = $cm;
    }


    /**
     * @param $object
     * @param string $to
     * @param array|null $props
     * @return mixed|string
     */
    public function output($object, $to = 'json', array $props = array())
    {
        switch($to) {
            case 'json':
                return $this->outputToJson($object,$props);
            case 'xml':
                return $this->outputToXml($object,$props);
            case 'array':
                return json_decode($this->outputToJson($object,$props));
        }
    }

    /**
     * @param array $props
     *
     * @return string
     */
    private function outputToJson($object,$props)
    {
        return json_encode($this->toArray($object,$props));
    }

    /**
     * @param array $props
     *
     * @return string
     *
     * @todo !! BE AWARE !! xmlrpc_encode is an experimental method.
     */
    private function outputToXml($object,$props)
    {
        return xmlrpc_encode($this->toArray($object,$props));
    }

    public function getToMapProperties($object)
    {
        return array_diff_key(get_object_vars($object), array_flip($this->ignored));
    }

    public function sortArray($array)
    {
        if(is_object($array) || is_array($array))
        {
            $array = (array) $array;
            foreach ($array as $index => $value)
            {
                $array[$index] = $this->sortArray($value);
            }
            ksort($array);
        }
        return $array;
    }

    public function toArray($object,$ignored=array())
    {
        $this->ignored=array_merge($ignored,$this->ignored);
        $array = $object instanceof BaseClass ? $this->toJson($this->getToMapProperties($object)): (is_object($object) ? get_object_vars($object) : $object);

        if(!is_array($array)) return $array;
        array_walk_recursive($array, function (&$value, $index) use($object,$ignored) {
            $value = $value instanceof BaseType ? (method_exists($object,'get'.ucfirst($index)) ? $object->{'get'.ucfirst($index)}() : $value->getValue()) : $value;
            $value = (is_object($value) && property_exists($value,'cluster') && property_exists($value,'position')) ? '#'.implode(':',(array)$value) : $value;

            if (is_object($value)) {
                $value = $this->toJson($value);
                $value = $this->toArray($value,$ignored);
            }else{
                $value = is_object($value) || is_array($value) ? (array) $value : $value;
            }
        });

        $this->sortArray($array);
        return $array;
    }
    public function toJson($object)
    {
        $data = (array) $object;
        if(count($data)==0)
        {
           return [];
        }
        $namespace = implode('', array_slice(explode('\\', get_class($object)),0, -1));
        $data['@class'] = implode('', array_slice(explode('\\', get_class($object)), -1));
        foreach ($data as $key => $value) {

            if($this->checkisRecord($value)) $value = $this->toJson($value);

            $newKey = preg_replace('/[^a-z]/i', null, $key);
            $newKey = str_replace(implode('', explode('\\', get_class($object))), null, $newKey);
            $newKey = str_replace($namespace, null, $newKey);
            if(strpos($newKey,"BiberLtdBundlePhorientOdmEntityBaseClass")!==false){
                unset($data[$key]);
                continue;
            }
            if (is_string($value)) {
                $data[$newKey] = str_replace('%', 'pr.', $value);
            } else {
                $data[$newKey] = $value;
            }
            unset($data[$key]);
            if (is_object($value)) {
                //$object = $value->__invoke();
                $data[$newKey] = $this->toJson($value);
            } elseif ($value instanceof \DateTime) {
                $data[$newKey] = $value->format('Y-m-d H:i:s');
            }
        }

        if(method_exists($object,'getRid'))
        $data['rid'] = $object->getRid();
        if (array_key_exists('version', $data)) {
            $data['@version'] = $data['version'];
            unset($data['version']);
        }
        if (array_key_exists('type', $data)) {
            $data['@type'] = $data['type'];
            unset($data['type']);
        }
        return $data;
    }

    public function objectToRecord($object)
    {
        $record = new Record();
        $data=[];
        foreach ($object as $index => $value)
        {
            switch ($index)
            {
                case '@type':
                    break;
                case '@fieldTypes':
                    break;
                case '@rid':
                    $record->setRid(new \PhpOrient\Protocols\Binary\Data\ID($value));
                    break;
                case '@version':
                    $record->getVersion($value);
                    break;
                case '@class':
                    $record->setOClass($value);
                    break;
                default:
                    if(is_array($value) && array_key_exists('@class',$value))
                    {
                        $value = $this->objectToRecord($value);
                    }
                    $data[$index]=$value;
            }
            $record->setOData($data);

        }
        return $record;
    }
    public function checkisRecord($data)
    {
        if($data instanceof Record) return true;
        if(is_array($data) && array_key_exists('@class',$data)) return true;
        if(is_object($data) && property_exists($data,'class')) return true;
        return false;
    }

    public function convertRecordToOdmObject($record,$bundle)
    {
        if(is_array($record) && array_key_exists('@class',$record))
        {
            $record = $this->objectToRecord($record);
            //dump($record);
        }
        $oClass = $record->getOClass();
        $oData = $record->getOData();;
        $class = $this->cm->getEntityPath($bundle).$oClass;
        if (!class_exists($class)) return $record->getOData();
        $entityClass =  new $class;
        $metadata = $this->cm->getMetadata($entityClass);
        $recordData = $oData;
        foreach ($metadata->getColumns()->toArray() as $propName => $annotations)
        {

            if(array_key_exists($propName, $recordData)) {
                $value = $this->checkisRecord($recordData[$propName]) ? $this->convertRecordToOdmObject($recordData[$propName],$bundle) : $this->arrayToObject($recordData[$propName],$bundle);
                $methodName = 'set' . ucfirst( $propName );
                if ( method_exists( $entityClass, $methodName ) ) {
                    $entityClass->{$methodName}( $value);
                } elseif( property_exists( $entityClass, $propName ) ) {
                    $entityClass->{$propName} = $value;
                } else {
                    // skip not existent configuration params
                }

            }
        }
        if(method_exists($entityClass,'setRid'))
            $entityClass->setRid($record->getRid());
        return $entityClass;
    }

    private function arrayToObject($arrayObject,$bundle)
    {

        if(is_array($arrayObject))
            foreach ($arrayObject as &$value) $value = $this->checkisRecord($value) ? $this->convertRecordToOdmObject($value,$bundle) : (is_array($value) ? $this->arrayToObject($value,$bundle): $value);

        return $arrayObject;
    }
}