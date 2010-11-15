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
 * Treatment class
 *
 * Represents an experiment treatment.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Treatment {

    /**
     * Name of the treatment.
     *
     * @var string
     */
    protected $_treatment_name;

    /**
     * Experiment that contains this treatment.
     *
     * @var Scenario_Experiment
     */
    protected $_parent;

    /**
     * Row ID (usually database related) of this treatment.
     *
     * @var int
     */
    protected $_row_id;

    /**
     * Constructs a new Treatment object.
     *
     * @param string $treatment_name Name of the treatment. If null, defaults to 50/50 chance between "default" and "alternate".
     * @param Scenario_Experiment $parent Experiment that contains this treatment.
     * @param int $row_id (Optional) Database row ID
     */
    public function __construct($treatment_name, Scenario_Experiment $parent, $row_id = null) {
        if ($treatment_name == null) {
            $treatment_name = (rand(0,1) == 1 ? 'default' : 'alternate');
        }
        $this->setTreatmentName($treatment_name);
        $this->setExperiment($parent);
        if ($row_id !== null) 
            $this->setRowId(intval($row_id));
    }

    /**
     * Gets the stored Row ID (db related)
     *
     * @return int
     */
    public function getRowId() {
        return $this->_row_id;
    }

    /**
     * Sets the stored Row ID
     *
     * @param int $val
     */
    public function setRowId($val) {
        if ($val === null || is_integer($val)) {
            $this->_row_id = $val;
        } else {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Row ID must be an integer or null.');
        }
    }

    /**
     * Sets the name of the treatment.
     *
     * @param string $name
     */
    public function setTreatmentName($name) {
        if (!(is_string($name) || $name === null)) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Treatment name must be set to a string or null.');
        }
        $this->_treatment_name = $name;
    }

    /**
     * Gets the name of the treatment.
     *
     * @return string
     */
    public function getTreatmentName() {
        return $this->_treatment_name;
    }

    /**
     * Get the treatment name. Short form for use via __get("name")
     *
     * @return string Name of the treatment
     */
    public function getName() {
        return $this->getTreatmentName();
    }

    /**
     * Set the parent experiment for this treatment.
     *
     * @param Scenario_Experiment $parent Parent experiment to set
     */
    public function setExperiment(Scenario_Experiment $parent) {
        $this->_parent = $parent;
    }

    /**
     * Get the parent experiment for this treatment.
     *
     * @return Scenario_Experiment The experiment that contains this treatment.
     */
    public function getExperiment() {
        return $this->_parent;
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        $method = 'get'.ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return null;
    }

    /**
     * Sets an identity/treatment combo for this treatment to "complete" (or "converted")
     *
     * @param Scenario_Identity $id Identity to set as complete.
     */
    public function finish(Scenario_Identity $id) {
        $adapter = $this->getCore()->getAdapter();
        if ($adapter !== null && $this->isValid())
            $adapter->FinishTreatment($this, $id);
    }

    /**
     * Check to see if the treatment object is complete & safe for use in other operations.
     *
     * @return bool Validity of the treatment object.
     */
    public function isValid() {
        return ($this->_parent !== null && $this->_parent instanceof Scenario_Experiment
                && is_string($this->_treatment_name) && !empty($this->_treatment_name));
    }

    /**
     * Get a safe reference to the core.
     *
     * @return Scenario_Core
     * @todo Pass a reference in upon construction rather than using the singleton.
     */
    public function getCore() {
        require_once 'Scenario/Core.php';
        return Scenario_Core::getInstance();
    }

}