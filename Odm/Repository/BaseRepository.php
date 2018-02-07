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

namespace BiberLtd\Bundle\Phorient\Odm\Repository;

use BiberLtd\Bundle\Phorient\Odm\Entity\BaseClass;
use BiberLtd\Bundle\Phorient\Odm\Exceptions\ClassMustBeSetException;
use BiberLtd\Bundle\Phorient\Odm\Exceptions\UniqueRecordExpected;
use BiberLtd\Bundle\Phorient\Odm\Responses\RepositoryResponse;
use BiberLtd\Bundle\Phorient\Odm\Types\BaseType;
use BiberLtd\Bundle\Phorient\Odm\Types\ORecordId;
use BiberLtd\Bundle\Phorient\Services\ClassManager;
use BiberLtd\Bundle\Phorient\Services\Metadata;
use BiberLtd\Bundle\Phorient\Services\OrientRest;
use BiberLtd\Bundle\Phorient\Services\PhpOrient;
use PhpOrient\Protocols\Binary\Data\ID;
use PhpOrient\Protocols\Binary\Data\Record;

abstract class BaseRepository implements RepositoryInterface
{
    protected $oService;
    protected $class;
    protected $controller;
    private $fetchPlan = false;
    private $cm;
    private $entityClass;
    private $bundle;
    private $response;
    private $raw;
    /**
     * @var Metadata $metadata;
     */
    private $metadata;

    /**
     * BaseRepository constructor.
     * @param ClassManager $cm
     */
    public function __construct(ClassManager $cm)
    {
        $this->cm = $cm;
        $this->oService = $cm->getConnection($cm->currentDb);
        $this->response = new RepositoryResponse();
    }

    /**
     * @param BaseClass $entity
     */
    public function setEntityClass(BaseClass $entity)
    {
        $this->entityClass = $entity;
    }

    /**
     * @param Metadata $metadata
     */
    public function setMetadata(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @return mixed
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @param mixed $bundle
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * @param array $collection
     * @param bool  $batch
     *
     * @return \BiberLtd\Bundle\Phorient\Odm\Responses\RepositoryResponse
     */
    public final function insert(array $collection, bool $batch = false)
    {
        $resultSet = [];
        if($batch) {
            $query = $this->prepareBatchInsertQuery($collection);
            $insertedRecords = $this->oService->command($query);
            $resultSet = $collection;
        } else {

            foreach($collection as $anEntity) {
                /**
                 * @var BaseClass $anEntity
                 */
                $query = $this->prepareInsertQuery($anEntity);
                /**
                 * @var Record $insertedRecord
                 */
                $insertedRecord = $this->oService->command($query);
                $anEntity->setRid($insertedRecord->getRid());
                $resultSet[] = $anEntity;
            }
        }

        $this->setResult($resultSet);
        return $this;
    }

    /**
     * @param array $collection
     *
     * @return array
     */
    public function update(array $collection)
    {
        $resultSet = [];
        foreach($collection as $anEntity) {
            /**
             * @var BaseClass $anEntity
             */
            if(!$anEntity->isModified()) {
                continue;
            }
            $query = $this->prepareUpdateQuery($anEntity);
            $result = $this->oService->command($query);
            if($result instanceof Record) {
                $resultSet[] = $anEntity;
            }
        }

        $this->setResult($resultSet);
        return $this;
    }

    /**
     * @param        $query
     * @param int    $limit
     * @param string $fetchPlan
     *
     * @return mixed
     */
    public function query($query, $limit = null, $fetchPlan = '*:0')
    {
        //$resultSet = $this->oService->query($query, $limit, $fetchPlan);
        $resultSet = $this->queryAsync($query, $limit, $fetchPlan);
        return $this->setResult($resultSet);
    }

    /**
     * @param string $query
     * @param array $params
     * @return array|null
     */
    public function command(string $query, array $params = [])
    {
        if($this->oService instanceof OrientRest){
            $result = $this->oService->command($query, $params);
        }
        else{
            $result = $this->oService->command($query);
        }
        return $result;
    }

    /**
     * @param $query
     * @param null $limit
     * @param string $fetchPlan
     * @param bool $limitless
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function queryAsync($query, $limit = null, $fetchPlan = '*:0', $limitless = false)
    {
        $return = new Record();
        $myFunction = function(Record $record) use ($return) {
            $return = $record;
        };
        $limit = $limit ?? -1;
        $options = ['fetch_plan' => $fetchPlan, '_callback' => $myFunction];
        $options = $limitless ? $options : array_merge($options, ['limit'=>$limit]);

        if($this->oService instanceof OrientRest){
            if($fetchPlan != '*:0'){
               // echo $fetchPlan;exit;
            }
            $resultSet = $this->oService->queryAsync($query, $options, $limit, $fetchPlan);
        }
        else{
            $resultSet = $this->oService->queryAsync($query, $options);
        }
        return $resultSet;
    }

    /**
     * @param string $fetchString
     */
    public function setFetchPlan($fetchString = '*:0')
    {
        $this->fetchPlan = $fetchString;
    }

    /**
     * @param array $collection
     *
     * @return array
     */
    public function delete(array $collection)
    {
        $resultSet = [];
        foreach($collection as $anEntity) {
            /**
             * @var BaseClass $anEntity
             */
            $query = 'DELETE FROM ' . $this->class . ' WHERE @rid = ' . $anEntity->getRid('string');
            $result = (bool) $this->oService->command($query);
            if($result) {
                $resultSet[] = $anEntity;
            }
        }

        $this->setResult($resultSet);
        return $this;
    }

    /**
     * @param array $collection
     *
     * @return string
     */
    private function prepareBatchInsertQuery(array $collection)
    {
        $props = $collection[0]->getProps();
        $query = 'INSERT INTO ' . $this->class . ' ';
        $propStr = '';
        $valueCollectionStr = '';

        foreach($props as $aProperty) {
            $propName = $aProperty->getName();
            $propStr .= $propName . ', ';
        }
        $propStr = ' (' . rtrim(', ', $propStr) . ') ';
        foreach($collection as $entity) {
            $valuesStr = '';
            foreach($props as $aProperty) {
                $propName = $aProperty->getName();
                $get = 'get' . ucfirst($propName);
                $value = $entity->$get();
                if($propName == 'rid') {
                    continue;
                }
                if(is_null($value) || empty($value)) {
                    continue;
                }
                $colDef = $entity->getColumnDefinition($propName);
                switch(strtolower($colDef->type)) {
                    case 'obinary':
                        /**
                         * @todo to be implemented
                         */
                        break;
                    case 'oboolean':
                        $valuesStr .= $entity->$get() . ', ';
                        break;
                    case 'odate':
                    case 'odatetime':
                        $dateStr = $entity->$get()->format('Y-m-d H:i:s');
                        $valuesStr .= '"' . $dateStr . '", ';
                        break;
                    case 'odecimal':
                    case 'ofloat':
                    case 'ointeger':
                    case 'oshort':
                    case 'olong':
                        $valuesStr .= $entity->$get() . ', ';
                        break;
                    case 'oembedded':
                    case 'oembeddedlist':
                    case 'oembeddedset':
                    case 'oembeddedmap':
                        $valuesStr .= json_encode($entity->$get()) . ', ';
                        break;
                    case 'olink':
                        if($entity->$get() instanceof BaseClass)
                            $valuesStr .= '"' . $entity->$get()->getRid('string') . '"';
                        elseif($entity->$get() instanceof ID) {
                            $id = $entity->$get();
                            $rid = '#' . $id->cluster . ':' . $id->position;
                            $valuesStr .= '"' . $rid . '", ';
                        }else{
                            $valuesStr .= 'NULL, ';
                        }
                        break;
                    case 'olinkbag':
                    case 'olinklist':
                    case 'olinkmap':
                    case 'olinkset':
                        /**
                         * @todo to be implemented
                         */
                        break;
                    case 'orecordid':
                        $valuesStr .= '"' . $entity->$get() . '", ';
                        break;
                    case 'ostring':
                        $valuesStr .= '"' . $entity->$get() . '", ';
                        break;
                }
            }
            $valueCollectionStr .= ' (' . rtrim($valuesStr, ', ') . '), ';
        }
        $valueCollectionStr = rtrim(', ', $valueCollectionStr);

        $query .= '(' . $propStr . ') VALUES ' . $valueCollectionStr;

        return $query;
    }

    /**
     * @param $entity
     *
     * @return string
     */
    private function prepareInsertQuery($entity)
    {
        $props = $entity->getProps();
        $query = 'INSERT INTO ' . $this->class . ' ';
        $propStr = '';
        $valuesStr = '';
        foreach($props as $aProperty) {
            $propName = $aProperty->getName();
            $get = 'get' . ucfirst($propName);
            //$get = $propName;


            if($propName == 'rid') {
                continue;
            }
            $value = $entity->$get();
            if(is_null($value) || empty($value)) {
                continue;
            }
            if($value instanceof BaseType) {
                if(is_array($value->getValue()) && count($value->getValue()) == 0) continue;
            }

            $propStr .= $propName . ', ';
            $colDef = $entity->getColumnDefinition($propName);
            switch(strtolower($colDef->type)) {
                case 'obinary':
                    /**
                     * @todo to be implemented
                     */
                    break;
                case 'oboolean':
                    $valuesStr .= $entity->$get() . ', ';
                    break;
                case 'odate':
                case 'odatetime':
                    $dateStr = $entity->$get()->format('Y-m-d H:i:s');
                    $valuesStr .= '"' . $dateStr . '", ';
                    break;
                case 'odecimal':
                case 'ofloat':
                case 'ointeger':
                case 'oshort':
                case 'olong':
                    $valuesStr .= $entity->$get() . ', ';
                    break;
                case 'oembedded':
                case 'oembeddedlist':
                case 'oembeddedmap':
                case 'oembeddedmap':
                    $valuesStr .= json_encode($entity->$get()) . ', ';
                    break;
                case 'olink':
                    if($entity->$get() instanceof BaseClass)
                        $valuesStr .= '"' . $entity->$get()->getRid('string') . '",';
                    elseif($entity->$get() instanceof ID) {
                        $id = $entity->$get();
                        $rid = '#' . $id->cluster . ':' . $id->position;
                        $valuesStr .= '"' . $rid . '", ';
                    }else{
                        $valuesStr .= 'NULL, ';
                    }
                    break;
                case 'olinkbag':
                case 'olinklist':
                case 'olinkmap':
                case 'olinkset':
                    $linklist = [];
                    if(is_array($entity->$get()))
                    {
                        foreach($entity->$get() as $index => $item)
                        {
                            $linklist[$index] = $item instanceof BaseClass ? $item->getRid('string') : $item;
                        }
                        $valuesStr .= '[' . implode(',', $linklist) . ']';
                    }else{
                        $valuesStr .= '[]';
                    }
                    $valuesStr .= ', ';
                    break;
                case 'orecordid':
                    $valuesStr .= '"' . $entity->$get() . '", ';
                    break;
                case 'ostring':
                    $valuesStr .= '"' . $entity->$get() . '", ';
                    break;
            }
        }
        $propStr = rtrim($propStr, ', ');
        $valuesStr = rtrim($valuesStr, ', ');
        $query .= '(' . $propStr . ') VALUES (' . $valuesStr . ')';

        return $query;
    }

    /**
     * @param $entity
     *
     * @return string
     */
    private function prepareUpdateQuery($entity)
    {

        $metadata = $this->cm->getMetadata($entity);

        $props = $metadata->getProps();
        $updatedProps = $entity->getUpdatedProps();
        $entity->setVersionHistory();
        $query = 'UPDATE ' . $this->class . ' SET ';
        $propStr = '';
        foreach($props as $aProperty) {
            $propName = $aProperty->getName();
            $get = 'get' . ucfirst($propName);
            if(method_exists($entity,'get' . ucfirst($propName))) {
                $value = $entity->$get();
            }else{
                $value = $entity->$propName;

            }

            if($propName == 'rid') {
                continue;
            }
            /*if($propName == 'rid' || !in_array($propName, $updatedProps)) {
                continue;
            }*/
            $colDef = $metadata->getColumn($propName);
            if(is_null($value) || empty($value) || (property_exists($colDef,'options') && key_exists('readOnly', $colDef->options) && $colDef->options['readOnly'] == true)) {
                continue;
            }
            $propStr .= $propName . ' = ';
            $valuesStr = '';

            $colType = property_exists($colDef,'type') ? $colDef->type : null;
            switch(strtolower($colType)) {
                case 'obinary':
                    /**
                     * @todo to be implemented
                     */
                    break;
                case 'oboolean':
                    $valuesStr .= $value;
                    break;
                case 'odate':
                case 'odatetime':
                    $dateStr = $value->format('Y-m-d H:i:s');
                    $valuesStr .= '"' . $dateStr . '"';
                    break;
                case 'odecimal':
                case 'ofloat':
                case 'ointeger':
                case 'oshort':
                case 'olong':
                    $valuesStr .= $value;
                    break;
                case 'oembedded':
                case 'oembeddedlist':
                case 'oembeddedmap':
                case 'oembeddedset':
                    $valuesStr .= json_encode($value);

                    break;
                case 'olink':
                    if($value instanceof BaseClass)
                        $valuesStr .= '"' . $value->getRid('string') . '"';
                    elseif($value instanceof ID) {
                        $id = $value;
                        $rid = '#' . $id->cluster . ':' . $id->position;
                        $valuesStr .= '"' . $rid . '"';
                    }else{
                        $valuesStr .= 'NULL';
                    }
                    break;
                case 'olinkbag':
                case 'olinkmap':
                case 'olinkset':
                case 'olinklist':
                    $linklist = [];
                    if(is_object($value) && method_exists($value,'getValue') && $value->getValue() != null)
                    {
                        foreach($value->getValue() as $index => $item)
                        {
                            $linklist[$index] = $item->getRid('string');
                        }
                        $valuesStr .= '"' . implode(',', $linklist) . '"';
                    }else{
                        $valuesStr .= '[]';
                    }
                    break;
                case 'orecordid':
                    $valuesStr .= '"' . $value . '"';
                    break;
                case 'ostring':
                    $valuesStr .= '"' . $value . '"';
                    break;
            }
            $propStr .= $valuesStr . ', ';
        }
        $propStr = rtrim($propStr, ', ');
        $query .= $propStr . ' WHERE @rid = ' . $entity->getRecordId('string');

        return $query;
    }

    /**
     * @param mixed $rid
     *
     * @return mixed
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\UniqueRecordExpected
     */
    /**
     * @param mixed $rid
     *
     * @return mixed
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\UniqueRecordExpected
     */
    public function selectByRid($rid, $class = null)
    {
        $class = $class ?? $this->class;
        if($rid instanceof ID) {
            $rid = $rid;
        } elseif($rid instanceof ORecordId) {
            $rid = $rid->getValue();
        } else {
            $oRid = new ORecordId($rid);
            $rid = $oRid->getValue();
        }
        /**
         * @var ID $rid
         */
        $q = 'SELECT FROM ' . $class . ' WHERE @rid = #' . $rid->cluster . ':' . $rid->position;
        $response = $this->query($q, 1);
        if(count($response->result) > 1) {
            throw new UniqueRecordExpected($class, $rid, 'ORecordId');
        }
        if(count($response->result) <= 0) {
            $this->setResult(null);
            return $this;
        }
        if($class != null) {
            $collection = [];

            foreach($response->result as $item) {
                $linkedObj = $this->getClassManager()->getEntityPath('AppBundle') . $class;
                $collection[] = new $linkedObj($this->getClassManager(), $item);
            }

            $this->setResult($collection[0]);
            return $this;
        } else {
            $this->setResult($response->result[0]);
            return $this;
        }

    }

    /**
     * @param array       $rids
     * @param string|null $class
     *
     * @return \BiberLtd\Bundle\Phorient\Odm\Responses\RepositoryResponse
     * @throws \BiberLtd\Bundle\Phorient\Odm\Exceptions\ClassMustBeSetException
     */
    public function listByRids(array $rids, string $class = null)
    {
        if(count($rids) < 1) {
            return new RepositoryResponse([]);
        }
        $class = $class ?? ($this->entityClass ?? null);
        if(is_null($class) || empty($class)) {
            throw new ClassMustBeSetException();
        }
        $convertdRids = [];
        foreach($rids as $rid) {
            if($rid instanceof ID) {
                $rid = $rid;
            } elseif($rid instanceof ORecordId) {
                $rid = $rid->getValue();
            } else {
                $oRid = new ORecordId($rid);
                $rid = $oRid->getValue();
            }
            $convertdRids[] = '#' . $rid->cluster . ':' . $rid->position;
        }
        $ridStr = implode(',', $convertdRids);
        unset($rids, $convertdRids);

        $q = 'SELECT FROM ' . $this->class . ' WHERE @rid IN [' . $ridStr . ']';
        $response = $this->query($q, 1);
        if(count($response->result) <= 0) {
            $this->setResult([]);
            return $this;
        }
        $collection = [];
        foreach($response->result as $item) {
            $collection[] = new $class($this->controller, $item);
        }

        $this->setResult($collection);
        return $this;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getClassManager()
    {
        return $this->cm;
    }

    public function getCount()
    {
        return is_array($this->getResult()) ? count($this->getResult()) : 0;
    }

    public function getSingularResult()
    {
        return $this->getCount() >0 ? $this->getResult()[0] : null;
    }

    public function getResult()
    {
        return  $this->response->getResult();
    }

    /**
     * @param $result
     * @return RepositoryResponse
     */
    public function setResult($result){
        $this->response = new RepositoryResponse();
        $this->response->raw = $result;
        $data=$this->cm->getDataManipulator()->odmToClass($result);
        $this->response->setResult($data);
        $this->response->raw =$this->cm->getDataManipulator()->odmToClass($result,false);
        return $this->response;
    }

    public function toJson()
    {
        $this->response->toJson();
    }

    public function setTotalRecords($count){
        $this->response->setTotalRecords($count);
        return $this;
    }
    public function getTotalRecords(){
        return $this->response->getTotalRecords();
    }

    public function getReponse()
    {
        return $this->response;
    }
}
