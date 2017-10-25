<?php
/**
 * Created by BoDev Office.
 * User: Erman Titiz ( @ermantitiz )
 * Date: 25/10/2017
 * Time: 13:40
 */

namespace BiberLtd\Bundle\Phorient\Services;


use BiberLtd\Bundle\Phorient\Services\Metadata;
use Doctrine\Common\Annotations\AnnotationReader as AnnotationReader;
use Doctrine\ORM\Mapping\Column;
class ClassMetadataFactory
{

    /**
     * @var array
     */
    private $metadataList = [];

    /**
     * {@inheritdoc}
     */
    public function getMetadata(ClassManager $classManager, $entityClass)
    {
        $metaHash = spl_object_hash($entityClass) . spl_object_hash($classManager);

        if (isset($this->metadataList[$metaHash])) {
            return $this->metadataList[$metaHash];
        }

        return $this->metadataList[$metaHash] = $this->createMetadata($classManager, $entityClass);
    }

    /**
     * @param ClassManager $classManager
     * @param $entityClass
     * @return mixed
     */
    private function createMetadata(ClassManager $classManager, $entityClass)
    {

        $metadata = new Metadata();
        $metadata = $this->prepareProps($entityClass, $metadata);
        $metadata = $this->preparePropAnnotations($entityClass, $metadata);

        return $metadata;

    }

    /**
     * @return $this
     */
    final private function prepareProps($class, Metadata $metadata)
    {
        $reflectionClass = new \ReflectionClass($class);
        $metadata->setProps($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC));

        return $metadata;
    }

    /**
     * @return $this
     */
    final private function preparePropAnnotations($class, Metadata $metadata)
    {
        $annoReader = new AnnotationReader();
        foreach($metadata->getProps() as $aProperty) {
            $propName=$aProperty->getName();
            $aPropertyReflection = new \ReflectionProperty(get_class($class), $propName);
            $propAnnotations=$annoReader->getPropertyAnnotations($aPropertyReflection);
            $metadata->setPropAnnotation($propName,$propAnnotations);
            if($propName == 'rid') {
                continue;
            }
            foreach($propAnnotations as $propAnnotation) {
                $metadata->setColumn($propName,$propAnnotation);
            }
        }

        return $this;
    }
}