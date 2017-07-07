<?php
/**
 * @package     bodev-core-bundles/php-orient-bundle
 * @subpackage  Odm/Types
 * @name        Short
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

class OShort extends Integer{

    /** @var float $value */
    protected $value;

    /**
     * @param float $value
     *
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\InvalidValueException
     */
    public function __construct($value){
        parent::__construct($value);
    }
}