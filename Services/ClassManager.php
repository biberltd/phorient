<?php
namespace BiberLtd\Bundle\Phorient\Services;
use BiberLtd\Bundle\Phorient\Odm\Entity\BaseClass;
use BiberLtd\Bundle\Phorient\Odm\Repository\BaseRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use PhpOrient\PhpOrient;
use Sgpatil\Orientphp\Client;
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
        $this->dataManipulator = new ClassDataManipulator();

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

        $client = new Client($this->config[$dbName]->getHost(), $this->config[$dbName]->getPort(),$this->config[$dbName]->getHost(), $dbName);
        $client->getTransport()->setAuth($this->config[$dbName]->getDbUser(), $this->config[$dbName]->getPassword());

        $this->oService[$dbName] = $client;
        $this->oService[$dbName]->getTransport()->setAuth($this->config[$dbName]->getDbUser(), $this->config[$dbName]->getPassword());
        return $this->setConnection($dbName);
    }

    /**
     * @param $dbName
     * @return $this
     */
    public function setConnection($dbName)
    {
        $this->currentDb = $dbName;
        return $this;
    }

    /**
     * @param null $dbName
     * @return mixed
     */
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
        $entityClass = $entityClass instanceof BaseClass ? get_class($entityClass) : $entityClass;
        return $this->cMetadataFactory->getMetadata($this,$entityClass);

    }
    public function convertRecordToOdmObject(Record $record,$bundle)
    {
        $class = $this->getEntityPath($bundle).$record->getOClass();
        if (!class_exists($class)) return $record->getOData();
        $entityClass =  new $class;
        $metadata = $this->getMetadata($entityClass);
        $recordData = $record->getOData();
        foreach ($metadata->getColumns()->toArray() as $propName => $annotations)
        {
            if(array_key_exists($propName, $recordData)) {
                $entityClass->$propName = $recordData[$propName] instanceof Record ? $this->convertRecordToOdmObject($recordData[$propName],$bundle) : $this->arrayToObject($recordData[$propName],$bundle);
            } else {
                $entityClass->$propName ==null;
            }
        }
        $entityClass->setRid($record->getRid());
        return $entityClass;
    }
    private function arrayToObject($arrayObject,$bundle)
    {

        if(is_array($arrayObject))
            foreach ($arrayObject as &$value) $value = $value instanceof Record ? $this->convertRecordToOdmObject($value,$bundle) : (is_array($value) ? $this->arrayToObject($value,$bundle): $value);

        return $arrayObject;
    }
    public function getDataManipulator()
    {
        return $this->dataManipulator;
    }
}
