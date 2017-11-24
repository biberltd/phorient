<?php
/**
 * Created by PhpStorm.
 * User: erman.titiz
 * Date: 12.06.2017
 * Time: 14:22
 */

namespace BiberLtd\Bundle\Phorient\Services;


use BiberLtd\Bundle\Phorient\Odm\Entity\BaseClass;
use BiberLtd\Bundle\Phorient\Odm\Repository\BaseRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Id;
use PhpOrient\PhpOrient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PhpOrient\Protocols\Binary\Data\Record;
use BiberLtd\Bundle\Phorient\Odm\Types\ORecordId;
use BiberLtd\Bundle\Phorient\Services\ClassDataManipulator;

class ClassManager
{

    private $oService;
    private $cRepositoryFactory;
    private $cMetadataFactory;
    private $config;
    private $annotationReader;
    private $container;
    public $currentDb;
    private $entityPath;
    private $dataManipulator;

    public function __construct(ContainerInterface $container =  null, CMConfig $config=null)
    {
        $this->config = $config;
        $this->container = $container;
        $this->cRepositoryFactory = new ClassRepositoryFactory();
        $this->cMetadataFactory = new ClassMetadataFactory();
        $this->annotationReader = new AnnotationReader();
        $this->dataManipulator = new ClassDataManipulator($this);

    }

    public function getAnnotationReader()
    {
        return $this->annotationReader;
    }
    public function createConnection($dbName,$dbInfo=null)
    {
        if($dbInfo==null)
        {
            $dbInfo =  $this->config;
            if(!isset($dbInfo['database'][$dbName])){
                throw new \Exception("Please check your parameters.yml for Orient Database connection");
            }
        }
        $this->config[$dbName] = new CMConfig();
        $this->config[$dbName]->setHost($dbInfo['database'][$dbName]['hostname']);
        $this->config[$dbName]->setPort($dbInfo['database'][$dbName]['port']);
        $this->config[$dbName]->setToken($dbInfo['database'][$dbName]['token']);
        $this->config[$dbName]->setDbUser($dbInfo['database'][$dbName]['username']);
        $this->config[$dbName]->setDbPass($dbInfo['database'][$dbName]['password']);
        /**
         * Protocol can be either of the following two values:
         * - binary
         * - rest
         */
        $this->config[$dbName]->setProtocol($dbInfo['database'][$dbName]['protocol']);
        switch($dbInfo['database'][$dbName]['protocol']){
            case 'binary':
                $this->oService[$dbName] = new PhpOrient($this->config[$dbName]->getHost(), $this->config[$dbName]->getPort(), $this->config[$dbName]->getToken());
                break;
            case 'rest':
                $this->oService[$dbName] = new OrientRest(
                    $this->config[$dbName]->getHost(),
                    $this->config[$dbName]->getPort(),
                    $dbName,
                    ['username' => $this->config[$dbName]->getDbUser(), 'password' => $this->config[$dbName]->getDbPass()],
                    $this->isSecure ?? false
                );
                break;
        }
        $this->oService[$dbName]->connect($this->config[$dbName]->getDbUser(), $this->config[$dbName]->getDbPass());
        $this->oService[$dbName]->dbOpen($dbName, $this->config[$dbName]->getDbUser(), $this->config[$dbName]->getDbPass());
        return $this->setConnection($dbName);
    }

    public function setConnection($dbName)
    {
        $this->currentDb = $dbName;
        return $this;
    }
    public function getConnection($dbName=null)
    {
        return $this->oService[$dbName ?? $this->currentDb];
    }

    /**
     * @param $entityName
     * @return BaseRepository
     */
    public function getRepository($entityName)
    {
        return $this->cRepositoryFactory->getRepository($this,$entityName);

    }

    public function setEntityPath($bundleName,$path)
    {
        $this->entityPath[$bundleName]=$path;
    }

    public function getEntityPath($bundleName=null)
    {
        if(is_null($bundleName)) $bundleName = $this->container->getRequest()->attributes->get('_template')->get('bundle');
        return $this->entityPath[$bundleName];
    }

    /**
     * @param $entityClass
     * @return Metadata
     */
    public function getMetadata($entityClass)
    {
        //$entityClass = (!class_exists($entityClass, false)) ? get_class($entityClass) : $entityClass;
        return $this->cMetadataFactory->getMetadata($this,$entityClass);

    }
    public function convertRecordToOdmObject($record,$bundle)
    {
        if(is_array($record) && array_key_exists('@class',$record))
        {
            $record = $this->dataManipulator->objectToRecord($record);
        }
        $oClass = $record->getOClass();
        $oData = $record->getOData();;
        $class = $this->getEntityPath($bundle).$oClass;
        if (!class_exists($class)) return $record->getOData();
        $entityClass =  new $class;
        $metadata = $this->getMetadata($entityClass);
        $recordData = $oData;
        foreach ($metadata->getColumns()->toArray() as $propName => $annotations)
        {

            if(array_key_exists($propName, $recordData)) {
                $value = $this->dataManipulator->checkisRecord($recordData[$propName]) ? $this->convertRecordToOdmObject($recordData[$propName],$bundle) : $this->arrayToObject($recordData[$propName],$bundle);
                $methodName = 'set' . ucfirst( $propName );
                if ( method_exists( $entityClass, $methodName ) ) {
                    $entityClass->{$methodName}( $value);
                } elseif( property_exists( $entityClass, $propName ) ) {
                    $entityClass->{$key} = $value;
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
            foreach ($arrayObject as &$value) $value = $this->dataManipulator->checkisRecord($arrayObject) ? $this->convertRecordToOdmObject($value,$bundle) : (is_array($value) ? $this->arrayToObject($value,$bundle): $value);

        return $arrayObject;
    }
    public function getDataManipulator()
    {
        return $this->dataManipulator;
    }

}
