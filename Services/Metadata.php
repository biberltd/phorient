<?php
/**
 * Created by BoDev Office.
 * User: Erman Titiz ( @ermantitiz )
 * Date: 25/10/2017
 * Time: 13:42
 */

namespace BiberLtd\Bundle\Phorient\Services;

use Doctrine\Common\Collections\ArrayCollection;

class Metadata
{

    /**
     * @var ArrayCollection
     */
    private $props = [];
    

    /**
     * @var ArrayCollection
     */
    private $propAnnotations = [];

    /**
     * @var ArrayCollection
     */
    private $columns = [];

    /**
     * Metadata constructor.
     */
    public function __construct()
    {
        $this->props = new ArrayCollection();
        $this->propAnnotations = new ArrayCollection();
        $this->columns = new ArrayCollection();
    }


    /**
     * @return array
     */
    public function getProps(): array
    {
        return $this->props;
    }

    /**
     * @param array $props
     */
    public function setProps(array $props)
    {
        $this->props = $props;
    }

    /**
     * @return array
     */
    public function getPropAnnotations(): array
    {
        return $this->propAnnotations;
    }

    /**
     * @param array $propAnnotations
     */
    public function setPropAnnotations(array $propAnnotations)
    {
        $this->propAnnotations = $propAnnotations;
    }

    /**
     * @return array
     */
    public function getPropAnnotation($key)
    {
        return $this->propAnnotations[$key];
    }

    public function addProp($prop)
    {
        if (!$this->props->contains($prop)) {
            $this->props->add($prop);
        }

        return $this;
    }

    public function removeProp($prop)
    {
        $this->props->removeElement($prop);

        return $this;
    }

    public function setPropAnnotation($key,$annotation)
    {
        $this->propAnnotations->set($key,$annotation);

        return $this;
    }
    public function setColumn($key,$columnAnnotation)
    {
        $this->columns->set($key,$columnAnnotation);

        return $this;
    }
    public function getColumn($key)
    {
        return array_key_exists($key, $this->columns->toArray()) ? $this->columns->get($key) : null;

    }

    public function removePropAnnotation($annotation)
    {
        $this->propAnnotations->removeElement($annotation);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getColumns(): ArrayCollection
    {
        return $this->columns;
    }

    /**
     * @param ArrayCollection $columns
     */
    public function setColumns(ArrayCollection $columns)
    {
        $this->columns = $columns;
    }

    public function addColumn($column)
    {
        if (!$this->columns->contains($column)) {
            $this->columns->add($column);
        }

        return $this;
    }

    public function removeColumn($column)
    {
        $this->columns->removeElement($column);

        return $this;
    }

    /**
     * @param $propertyName
     *
     * @return mixed
     */
    public function getColumnProperty($propertyName,$property)
    {
        $colDef = $this->getColumn($propertyName);

        return is_null($colDef) ? null : (property_exists($colDef,$property) ? $colDef->{$property} : null);
    }


}