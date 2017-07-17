<?php
/**
 * @package     bodev-core-bundles/php-orient-bundle
 * @subpackage  Odm/Types
 * @name        LinkBag
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
use PhpOrient\Protocols\Binary\Data\ID as ID;

class OLinkBag extends OrientCollection
{

    /** @var array $value */
    protected $value;

    /**
     * @param array $value
     *
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function __construct(array $value = [])
    {
        parent::__construct('OLinkBag', $value);
    }

    /**
     * @return array
     */
    public function getValue($embedded = false)
    {
        return $this->value;
    }

    /**
     * @param array $value
     *
     * @return $this
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function setValue($value)
    {
        if($this->validateValue($value)) {
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
    public function validateValue($value)
    {
        if(!is_array($value)) {
            throw new InvalidValueException($this);
        }
        foreach($value as $item) {
            if(!$item instanceof ID) {
                throw new InvalidValueException($this);
            }
        }

        return true;
    }
}