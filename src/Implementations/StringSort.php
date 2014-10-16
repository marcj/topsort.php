<?php

namespace MJS\TopSort\Implementations;

use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;

/**
 * A topological sort implementation based on string manipulations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class StringSort extends ArraySort
{

    /**
     * @var string
     */
    protected $sorted;

    /**
     * @var string
     */
    protected $delimiter = "\0";

    /**
     * {@inheritDoc}
     */
    protected function addToList($element)
    {
        $this->sorted .= $element->id . $this->delimiter;
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        return explode($this->delimiter, rtrim($this->doSort(), $this->delimiter));
    }

    /**
     * Sorts dependencies and returns internal used data structure.
     *
     * @return string
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    public function doSort()
    {
        $this->sorted = '';

        foreach ($this->elements as $element) {
            $parents = [];
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}