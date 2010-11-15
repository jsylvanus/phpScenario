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
 * Result object
 *
 * Essentially a storage object for all data related to a given result.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Result {

    /**
     *
     * @var Scenario_Identity
     */
    protected $_ident;

    /**
     *
     * @var bool
     */
    protected $_completed;

    /**
     *
     * @var Scenario_Treatment
     */
    protected $_treatment;

    /**
     * Experiment. May be a string or Scenario_Experiment object.
     *
     * @var mixed
     */
    protected $_experiment;

    public function __construct($experiment, $treatment, $identity, $completed) {
        $this->setExperiment($experiment);
        $this->setTreatment($treatment);
        $this->setIdentity($identity);
        $this->_completed = $completed;
    }

    /**
     *
     * @return bool Whether the treatment was completed or not.
     */
    public function isCompleted() {
        return $this->_completed;
    }

    /**
     * Gets the associated experiment, optionally resolving it to an object if it's a string.
     *
     * @param bool $tryResolve (Optional) Whether the experiment should be resolved to an object if it is a string.
     * @return Scenario_Experiment The experiment associated with this result.
     */
    public function getExperiment($tryResolve = true) {
        if ($this->_experiment === null)
            return null;
        else if (is_string($this->_experiment) && $tryResolve) {
            $result = $this->getCore()->GetExperimentByName($this->_experiment);
            if ($result instanceof Scenario_Experiment)
                $this->_experiment = $result;
        }
        return $this->_experiment;
    }

    /**
     * Returns the treatment associated with this result.
     *
     * @return Scenario_Treatment
     */
    public function getTreatment() {
        if (is_string($this->_treatment)) {
            require_once 'Scenario/Treatment.php';
            $this->_treatment = new Scenario_Treatment($this->_treatment, $this->getExperiment());
            return $this->_treatment;
        } else if ($this->_treatment instanceof Scenario_Treatment || $this->_treatment === null) {
            return $this->_treatment;
        } else {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Treatment was not a string, null, or instance of Scenario_Treatment.');
        }
    }

    /**
     * Returns the identity associated with this result.
     *
     * @return Scenario_Identity
     */
    public function getIdentity() {
        return $this->_ident;
    }

    /**
     * Set the associated experiment.
     *
     * @param mixed $value Must be null, a string, or an instance of Scenario_Experiment
     */
    public function setExperiment($value) {
        if (is_string($value)) {
            $this->_experiment = $value;
            $this->getExperiment(); // attempt to resolve the experiment name
        } else if ($value instanceof Scenario_Experiment || $value === null) {
            $this->_experiment = $value;
        } else {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Experiment must be set to null, a string, or an instance of Scenario_Experiment.');
        }
    }

    /**
     * Set the associated treatment.
     * 
     * @param mixed $value Must be a null, a string, or an instance of Scenario_Treatment.
     */
    public function setTreatment($value) {
        if (is_string($value)) {
            if ($this->getExperiment() !== null) {
                require_once 'Scenario/Treatment.php';
                $this->_treatment = new Scenario_Treatment($value, $this->getExperiment(), null);
            }
        } else if ($value instanceof Scenario_Treatment || $value === null) {
            $this->_treatment = $value;
        } else {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Treatment must be set to null, a string, or an instance of Scenario_Treatment.');
        }
    }

    /**
     * Set the associated identity.
     *
     * @param mixed $value Must be null, a string, or an instance of Scenario_Identity.
     */
    public function setIdentity($value) {
        require_once 'Scenario/Identity.php';
        if (is_string($value)) {
            $this->_ident = new Scenario_Identity($value);
        } else if ($value instanceof Scenario_Identity || $value === null) {
            $this->_ident = $value;
        } else {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Identity must be set to null, a string, or an instance of Scenario_Identity.');
        }
    }

    /**
     * Gets the core reference appropriate to this instance.
     *
     * @return Scenario_Core
     * @todo Use a reference to the core rather than the singleton.
     */
    public function getCore() {
        return Scenario_Core::getInstance();
    }

}
