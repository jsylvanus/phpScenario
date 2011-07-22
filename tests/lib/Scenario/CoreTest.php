<?php

class Scenario_CoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Scenario
     */
    protected $_object;

    public function setUp()
    {
        $this->_object = new Scenario();
    }

    public function testGetInstance()
    {
        $instance = Scenario::getInstance();

        $this->assertInstanceOf('Scenario_Core', $instance);
    }
}