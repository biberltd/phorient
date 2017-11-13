<?php
/**
 * @package     bodev-core-bundles/php-orient-bundle
 * @subpackage  Odm/Types
 * @name        DateTime
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

class ODateTime extends BaseType{

    /** @var \DateTime $value */
    protected $value;

    protected $pattern = 'Y-m-d\TH:i:s\Z';
    /**
     * @param \DateTime $value
     *
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function __construct($value = null, $embedded = false){
        parent::__construct('ODateTime', $value, $embedded);
    }

    /**
     * @return \DateTime
     */
    public function getValue($embedded = false){
        if (!$embedded && $this->value != null) {
            return $this->value->format($this->pattern);
        }
        return $this->value;
    }

    public function setPattern($format)
    {
        $this->pattern = $format;
        return $this;
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
    /*
     * @param mixed $value
     *
     * @return bool
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function validateValue($value){
        if($value != null && !$value instanceof \DateTime){
            throw new InvalidValueException($this);
        }
        return true;
    }

}