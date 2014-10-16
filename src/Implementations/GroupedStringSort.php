<?php

namespace MJS\TopSort\Implementations;

/**
 * Implements grouped topological-sort based on string manipulation.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GroupedStringSort extends GroupedArraySort
{
    protected $delimiter = "\0";

    /**
     * @param object  $element
     * @param integer $minLevel
     */
    protected function injectElement($element, $minLevel)
    {
        if ($group = $this->getFirstGroup($element->type, $minLevel)) {
            //add this element into a group
            $this->addItemAt($group->position, $element);
            $group->length++;

            //increase all following groups +1
            $i = $group->position;
            foreach ($this->groups as $tempGroup) {
                if ($tempGroup->position > $i) {
                    $tempGroup->position += strlen($element->id . $this->delimiter);
                }
            }

            $element->addedAtLevel = $group->level;
        } else {
            //just append this element at the end
            $this->groups[] = (object)[
                'type' => $element->type,
                'level' => $this->groupLevel,
                'position' => $this->position,
                'length' => 1
            ];
            $element->addedAtLevel = $this->groupLevel;

            $id = $element->id . $this->delimiter;

            $this->sorted .= $id;
            $this->position += strlen($id);
            $this->groupLevel++;
        }
    }

    /**
     * @param integer $position
     * @param object  $element
     */
    public function addItemAt($position, $element)
    {
        $this->sorted = substr_replace($this->sorted, $element->id . $this->delimiter, $position, 0);
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        return explode($this->delimiter, rtrim($this->doSort(), $this->delimiter));
    }

    /**
     * {@inheritDoc}
     */
    public function getGroups()
    {
        $position = 0;
        return array_map(function($group) use (&$position) {
            $group->position = $position;
            $position += $group->length;
            return $group;
        }, $this->groups);
    }

    /**
     * {@inheritDoc}
     */
    public function doSort()
    {
        $this->position = 0;
        $this->sorted = '';

        foreach ($this->elements as $element) {
            $parents = [];
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}