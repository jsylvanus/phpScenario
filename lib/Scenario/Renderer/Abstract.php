<?php
/**
 * phpScenario
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.phpscenario.org/license.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@phpscenario.org so we can send you a copy immediately.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */

/**
 * Renderer abstract
 *
 * Defines the basic structure of a renderer object that should be passed
 * to Scenario_ResultSet rendering methods, or used by itself.
 *
 * @abstract
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
abstract class Scenario_Renderer_Abstract {

    /**
     * Array of key=>value parameters/options.
     *
     * @var array
     */
    protected $_params;

    /**
     * @param array $options The options for this renderer.
     */
    public function __construct($options = array()) {
        $this->setParams($options);
    }

    /**
     * Set parameters to pass to aggregator and render function. Overwrites current params.
     *
     * @param array $params Parameters to set (key => value)
     */
    public function setParams(array $params = array(), $clearFirst = false) {
        if ($clearFirst) 
            $this->_params = array();
        if (is_array($this->_params))
            $this->_params = array_merge($this->_params, $params);
        else
            $this->_params = $params;
    }

    /**
     * Return the current parameter array.
     *
     * @return array
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * Set a specific parameter.
     *
     * @param string $name  Key for the associated value.
     * @param mixed $value  Value to set.
     */
    public function setParam($name, $value) {
        if (!is_array($this->_params))
            $this->_params = array();
        $this->_params[$name] = $value;
    }

    /**
     * Unset a specific parameter.
     *
     * @param string $name The parameter to unset.
     */
    public function clearParam($name) {
        if (is_array($this->_params) && array_key_exists($name, $this->_params))
            unset($this->_params[$name]);
    }

    /**
     * Retrieve a parameter from the renderer's settings.
     *
     * @param string $name  Name of the parameter to retrieve.
     * @return mixed  The value of the specified parameter, or null if it does not exist.
     */
    public function getParam($name) {
        if (array_key_exists($name, $this->_params))
            return $this->_params[$name];
        return null;
    }

    /**
     * renderSet
     *
     * Render the resultset of a single experiment
     */
    public abstract function renderSet(Scenario_ResultSet $results);

}