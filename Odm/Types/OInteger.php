<?php
/**
 * @package     bodev-core-bundles/php-orient-bundle
 * @subpackage  Odm/Types
 * @name        Integer
 *
 * @author      Biber Ltd. (www.biberltd.com)
 * @author      Can Berkol
 *
 * @copyright   Biber Ltd. (C) 2015
 *
 * @version     1.0.0
 */

namespace BiberLtd\Bundle\Phorient\Odm\Types;

use BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException;

class OInteger extends BaseType{

    /** @var  $value integer */
    protected $value;

    /**
     * @param integer $value
     *
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function __construct($value=null){
        parent::__construct('OInteger', $value);
    }

    /**
     * @return int
     */
    public function getValue($embedded = false){
        return $this->value;
    }

    /**
     * @param $value
     *
     * @return $this
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function setValue($value){
        if($this->validateValue($value)){
            $this->value = $value;
        }
        return $this;
    }
    /**
     * @param mixed $value
     *
     * @return bool
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function validateValue($value){
        if(is_numeric($value)){
            $value = (int) $value;
        }
        if(!is_integer($value) && !is_null($value)){
            throw new InvalidValueException($this);
        }
        return true;
    }

}