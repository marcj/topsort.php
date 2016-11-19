<?php

namespace MJS\TopSort\Tests;

use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\ArraySort;
use MJS\TopSort\Implementations\FixedArraySort;
use MJS\TopSort\Implementations\StringSort;
use MJS\TopSort\TopSortInterface;

class SimpleSortTest extends \PHPUnit_Framework_TestCase
{

    public function provideImplementations()
    {
        return array(
            array(new ArraySort()),
            array(new StringSort()),
            array(new FixedArraySort())
        );
    }

    /**
     * @dataProvider             provideImplementations
     * @expectedException        \MJS\TopSort\CircularDependencyException
     * @expectedExceptionMessage Circular dependency found: car1->owner1->car1
     *
     * @param TopSortInterface $sorter
     */
    public function testCircular(TopSortInterface $sorter)
    {
        $sorter->add('car1', array('owner1'));
        $sorter->add('owner1', array('car1'));
        $sorter->sort();
    }

    /**
     * @dataProvider             provideImplementations
     *
     * @param TopSortInterface $sorter
     */
    public function testDisabledCircularException(TopSortInterface $sorter)
    {
        $sorter->setThrowCircularDependency(false);
        $sorter->add('car1', array('owner1'));
        $sorter->add('owner1', array('car1'));
        $result = $sorter->sort();

        $this->assertEquals(array('owner1', 'car1'), $result);
    }

    /**
     * @dataProvider             provideImplementations
     * @expectedException        \MJS\TopSort\ElementNotFoundException
     * @expectedExceptionMessage Dependency `car2` not found, required by `owner1`
     *
     * @param TopSortInterface $sorter
     */
    public function testNotFound(TopSortInterface $sorter)
    {
        $sorter->setThrowCircularDependency(true);
        $sorter->add('car1', array('owner1'));
        $sorter->add('owner1', array('car2'));
        $sorter->sort();
    }

    /**
     * @dataProvider             provideImplementations
     *
     * @param TopSortInterface $sorter
     */
    public function testCircularException(TopSortInterface $sorter)
    {
        $sorter->setThrowCircularDependency(true);
        $sorter->add('car1', array('owner1'));
        $sorter->add('owner1', array('brand1'));
        $sorter->add('brand1', array('car1'));

        try {
            $sorter->sort();
            $this->fail('This must fail');
        } catch(CircularDependencyException $e) {
            $this->assertEquals(array('car1', 'owner1', 'brand1'), $e->getNodes());
            $this->assertEquals('car1', $e->getStart());
            $this->assertEquals('brand1', $e->getEnd());
        }
    }

    /**
     * @dataProvider             provideImplementations
     *
     * @param TopSortInterface $sorter
     */
    public function testCircularExceptionInterceptor(TopSortInterface $sorter)
    {
        $sorter->setThrowCircularDependency(true);
        $intercepted = false;
        $sorter->setCircularInterceptor(function() use (&$intercepted) {
            $intercepted = true;
        });
        $sorter->add('car1', array('owner1'));
        $sorter->add('owner1', array('brand1'));
        $sorter->add('brand1', array('car1'));

        $sorter->sort();
        $this->assertTrue($intercepted, 'Interception method must be called since a circular dependency has found');
    }

    public function testConstructor()
    {
        $elements = array('car1' => array('brand1'), 'car2' => array('brand2'), 'brand1' => array(), 'brand2' => array());
        $sorter = new ArraySort($elements, true);
        $this->assertTrue($sorter->isThrowCircularDependency());
        $this->assertEquals(array('brand1', 'car1', 'brand2', 'car2'), $sorter->sort());
    }

    /**
     * @dataProvider             provideImplementations
     *
     * @param TopSortInterface $sorter
     */
    public function testNotFoundException(TopSortInterface $sorter)
    {
        $sorter->setThrowCircularDependency(true);
        $sorter->add('car1', array('owner1'));
        $sorter->add('owner1', array('car2'));

        $this->assertEquals(true, $sorter->isThrowCircularDependency());

        try {
            $sorter->sort();
            $this->fail('This must fail');
        } catch(ElementNotFoundException $e) {
            $this->assertEquals('owner1', $e->getSource());
            $this->assertEquals('car2', $e->getTarget());
        }
    }

    /**
     * @dataProvider provideImplementations
     */
    public function testImplementationsBlub(TopSortInterface $sorter)
    {
        for ($i = 0; $i < 2; $i++) {
            $sorter->add('car' . $i, array('owner' . $i, 'brand' . $i));
            $sorter->add('owner' . $i, array('brand' . $i));
            $sorter->add('brand' . $i);
        }

        $sorter->add('sellerX', array('brandX3'));
        $sorter->add('brandY', array('sellerX', 'brandX2'));
        $sorter->add('brandX');
        $sorter->add('brandX2', array('brandX', 'brandX3'));
        $sorter->add('brandX3');

        $result = $sorter->sort();

        $expected = array(
            'brand0',
            'owner0',
            'car0',
            'brand1',
            'owner1',
            'car1',
            'brandX3',
            'sellerX',
            'brandX',
            'brandX2',
            'brandY',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider provideImplementations
     */
    public function testImplementationsSimpleDoc(TopSortInterface $sorter)
    {
        $sorter->add('car1', array('owner1', 'brand1'));
        $sorter->add('owner3', array('brand2'));
        $sorter->add('owner2', array('brand2'));
        $sorter->add('brand1');
        $sorter->add('brand2');
        $sorter->add('owner1', array('brand1'));

        $result = $sorter->sort();

        $expected = explode(', ', 'brand1, owner1, car1, brand2, owner3, owner2');

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider provideImplementations
     */
    public function testImplementationsSimple(TopSortInterface $sorter)
    {

        $sorter->add('brand1');
        $sorter->add('car1', array('brand1'));

        $sorter->add('car2', array('brand2'));
        $sorter->add('brand2');

        $result = $sorter->sort();

        $expected = explode(', ', 'brand1, car1, brand2, car2');

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider provideImplementations
     */
    public function testImplementations(TopSortInterface $sorter)
    {
        for ($i = 0; $i < 3; $i++) {
            $sorter->add('car' . $i, array('owner' . $i, 'brand' . $i));
            $sorter->add('owner' . $i, array('brand' . $i));
            $sorter->add('brand' . $i);
        }

        $result = $sorter->sort();

        $expected = array(
            'brand0',
            'owner0',
            'car0',
            'brand1',
            'owner1',
            'car1',
            'brand2',
            'owner2',
            'car2'
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider provideImplementations
     */
    public function testImplementations2(TopSortInterface $sorter)
    {
        for ($i = 0; $i < 3; $i++) {
            $sorter->add('owner' . $i, array('brand' . $i));
            $sorter->add('car' . $i, array('owner' . $i, 'brand' . $i));
            $sorter->add('brand' . $i);
        }

        $result = $sorter->sort();

        $expected = array(
            'brand0',
            'owner0',
            'car0',
            'brand1',
            'owner1',
            'car1',
            'brand2',
            'owner2',
            'car2'
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider provideImplementations
     */
    public function testImplementations3(TopSortInterface $sorter)
    {
        for ($i = 0; $i < 3; $i++) {
            $sorter->add('owner' . $i, array('brand' . $i));
            $sorter->add('brand' . $i);
            $sorter->add('car' . $i, array('owner' . $i, 'brand' . $i));
        }

        $result = $sorter->sort();

        $expected = array(
            'brand0',
            'owner0',
            'car0',
            'brand1',
            'owner1',
            'car1',
            'brand2',
            'owner2',
            'car2'
        );

        $this->assertEquals($expected, $result);
    }
}