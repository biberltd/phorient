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

class ClassDataManipulator
{

    private $ignored = array('index', 'parent','modified','versionHash', 'typePath', 'updatedProps', 'dateAdded', 'dateRemoved', 'versionHistory','dtFormat');

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
                return json_decode($this->outputToJson($object,$props), true);
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
        $array = $object instanceof BaseClass ? $this->getToMapProperties($object) : (is_object($object) ? get_object_vars($object) : $object);
        if(!is_array($array)) return $array;
        array_walk_recursive($array, function (&$value, $index) use($object) {
            $value = $value instanceof BaseType ? (method_exists($object,'get'.ucfirst($index)) ? $object->{'get'.ucfirst($index)}() : $value->getValue()) : $value;
            $value = $value instanceof ID ? '#'.$value->cluster.':'.$value->position : $value;
            if ($value instanceof BaseClass) {
                $value = $this->toArray($value);
            }else{
                $value = is_object($value) || is_array($value) ? (array) $value : $value;
            }
        });
        $this->sortArray($array);
        return $array;
    }
}