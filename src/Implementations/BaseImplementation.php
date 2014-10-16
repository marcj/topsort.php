<?php


namespace MJS\TopSort\Implementations;


use MJS\TopSort\CircularDependencyException;

abstract class BaseImplementation
{
    /**
     * @var bool
     */
    protected $throwCircularDependency = true;

    public function __construct(array $elements = array(), $throwCircularDependency = true)
    {
        $this->set($elements);
        $this->throwCircularDependency = $throwCircularDependency;
    }

    abstract public function set(array $elements);

    /**
     * @param object   $element
     * @param object[] $parents
     *
     * @throws CircularDependencyException
     */
    protected function throwCircularExceptionIfNeeded($element, $parents)
    {
        if (isset($parents[$element->id])) {
            if (!$this->throwCircularDependency) {
                return;
            }

            $nodes = array_keys($parents);
            $nodes[] = $element->id;
            throw CircularDependencyException::create($nodes);
        }
    }

    /**
     * @return boolean
     */
    public function isThrowCircularDependency()
    {
        return $this->throwCircularDependency;
    }

    /**
     * @param boolean $throwCircularDependency
     */
    public function setThrowCircularDependency($throwCircularDependency)
    {
        $this->throwCircularDependency = $throwCircularDependency;
    }
}