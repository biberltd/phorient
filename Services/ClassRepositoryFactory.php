<?php
/**
 * Created by PhpStorm.
 * User: erman.titiz
 * Date: 12.06.2017
 * Time: 14:27
 */

namespace BiberLtd\Bundle\Phorient\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Entity;

class ClassRepositoryFactory
{

    private $repositoryList = array();

    /**
     * {@inheritdoc}
     */
    public function getRepository(ClassManager $classManager, $entityName)
    {
        $repositoryHash = $entityName . spl_object_hash($classManager);

        if (isset($this->repositoryList[$repositoryHash])) {
            return $this->repositoryList[$repositoryHash];
        }

        return $this->repositoryList[$repositoryHash] = $this->createRepository($classManager, $entityName);
    }

    /**
     * @param ClassManager $classManager
     * @param $entityName
     * @return mixed
     */
    private function createRepository(ClassManager $classManager, $entityName)
    {

        if(strpos($entityName,':')>-1)
        {
            list($bundle,$entityName) = explode(':',$entityName);
        }else{
            $bundle = 'AppBundle';
        }
        $entityClass = $classManager->getEntityPath($bundle).$entityName;

        /**
         * @var Metadata $metadata
         */
        $metadata = $classManager->getMetadata($entityClass);

        $rc = new \ReflectionClass($entityClass);
        /**
         * @var AnnotationReader $an
         */
        $an = $classManager->getAnnotationReader();
        $result = $an->getClassAnnotation($rc,Entity::class);

        if($result instanceof Entity)
        {
            $repositoryClassName = $result->repositoryClass;
        }else{

             /** todo: eğer tanımlı bir repo yok ise default repo yüklenecek */
            $repositoryClassName = str_replace('Entity','Repository',$this->getNameSpace()).$entityName.'Repository';
        }

        $repository =  new $repositoryClassName($classManager);
        $repository->setMetadata($metadata);
        $repository->setBundle($bundle);
        return $repository;
    }
}