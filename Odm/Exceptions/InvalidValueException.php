<?php
/**
 * @package     bodev-core-bundles/php-orient-bundle
 * @subpackage  Odm/exceptions
 * @name        InvalidValueException
 *
 * @author      Biber Ltd. (www.biberltd.com)
 * @author      Can Berkol
 *
 * @copyright   Biber Ltd. (C) 2015
 *
 * @version     1.0.0
 */

namespace BiberLtd\Bundle\Phorient\Odm\Exceptions;

class InvalidValueException extends \Exception{

	/**
	 * @param \BiberLtd\Bundle\Phorient\Odm\Exceptions\object|null $type Type of value.
	 */
	public function __construct($type = null){
		$this->message = 'An invalid value provided.';

		if(!is_null($type)){
			$this->message .= ' The value must be a type of '.get_class($type);
		}
	}
}