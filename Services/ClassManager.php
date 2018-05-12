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
    private $currentBundle;
    public $fileFactory;

    public function __construct(ContainerInterface $container =  null, CMConfig $config=null)
    {
        $this->config = $config;
        $this->container = $container;
        $this->cRepositoryFactory = new ClassRepositoryFactory();
        $this->cMetadataFactory = new ClassMetadataFactory();
        $this->annotationReader = new AnnotationReader();
        $this->dataManipulator = new ClassDataManipulator($this);
        $this->fileFactory = new FileFactory($this->container->getParameter('kernel.cache_dir'));

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
        $this->currentBundle = $bundleName;
        $this->entityPath[$bundleName]=$path;
    }

    public function getEntityPath($bundleName=null)
    {
        $bundleName= $bundleName ?? $this->currentBundle;
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
    public function getDataManipulator()
    {
        return $this->dataManipulator;
    }
    public function persist(&$entityClass)
    {
        $object = $this->getDataManipulator()->objectToRecordArray($entityClass);

        $class = $object['@class'];
        unset($object['@class']);
        unset($object['@type']);
        unset($object['@version']);
        if(!is_null($entityClass->getRid()))
        {
            $sql = "UPDATE ".$entityClass->getRid()." MERGE " . json_encode($object);
        }else{
            $sql = "INSERT INTO ".$class." CONTENT " . json_encode($object);
        }

        $result = $this->oService[$this->currentDb]->command($sql);

        if (!is_null($result)) {

            $entityClass->setType($result[0]['@type']);
            $entityClass->setVersion($result[0]['@version']);
            if(array_key_exists('@rid',$result[0]))
            {
                $entityClass->setRecordId(new \PhpOrient\Protocols\Binary\Data\ID($result[0]['@rid']));
                $entityClass->setClass($result[0]['@class']);
            }
            if(property_exists($entityClass,'id') && array_key_exists('id',$result[0]))
            {
                $entityClass->setId($result[0]['id']);
            }
        }
        return $result;
    }

}
