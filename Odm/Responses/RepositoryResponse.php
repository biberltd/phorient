<?php
/**
 * @package     bodev-core-bundles/php-orient-bundle
 * @subpackage  Odm/Repository
 * @name        BaseRepository
 *
 * @author      Biber Ltd. (www.biberltd.com)
 * @author      Can Berkol
 *
 * @copyright   Biber Ltd. (C) 2015
 *
 * @version     1.0.0
 */

namespace BiberLtd\Bundle\Phorient\Odm\Responses;


use BiberLtd\Bundle\Phorient\Services\ClassDataManipulator;

class RepositoryResponse{
	/**
	 * @var int
	 */
	public $code;
	/**
	 * @var mixed
	 */
	public $result;

	/**
	 * @var mixed
	 */
	public $execution;

	/**
	 * @var mixed
	 */
	public $totalRecords;
	/**
	 * RepositoryResponse constructor.
	 *
	 * @param mixed $result
	 * @param int   $code
	 */
	public function __construct($result = null, $code = 200){
		$this->code = $code;
		$this->result = $result;
		$this->totalRecords = 0;
		$this->execution = new \stdClass();
		$this->execution->start = microtime(true);
		$this->endExecution();
	}

	/**
	 * @return $this
	 */
	public function endExecution(){
		$this->execution->end = microtime(true);
		$this->execution->duration = ($this->execution->end - $this->execution->start);
		return $this;
	}

    public function getCount()
    {
        return is_array($this->result) ? count($this->result) : 0;
    }

    public function getSingularResult()
    {
        return $this->getCount() >0 ? $this->result[0] : null;
    }

    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $result
     * @return $this
     */
	public function setResult($result){
		$this->result = $result;
		return $this;
	}

	public function toJson()
    {
        $this->result = (new ClassDataManipulator())->output($this->result,'array');
        return $this;
    }

    /**
     * @param $count
     * @return $this
     */
    public function setTotalRecourds($count){
	    $this->totalRecords = $count;
	    return $this;
    }
}