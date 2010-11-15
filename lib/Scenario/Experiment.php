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
 * Experiment class
 *
 * Handles experiment-related functions, stores experiment data.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Experiment {

    /**
     * Name of the experiment.
     *
     * @var string
     */
    protected $_experiment_id;

    /**
     * Row ID for the experiment, usually used by databases.
     *
     * @var int
     */
    protected $_row_id;

    /**
     * Construct a new Experiment object.
     *
     * @param string $experiment_id
     * @param int|null $row_id
     * @param bool $create
     */
    public function __construct($experiment_id, $row_id = null, $create = true) {
        $this->setExperimentID($experiment_id);
        if ($row_id == null) {
            $experiment = $this->getCore()->getAdapter()->GetExperiment($experiment_id);
            if ($experiment === null && $create) {
                $experiment = $this->getCore()->getAdapter()->AddExperiment($experiment_id);
            }
            if ($experiment !== null)
                $row_id = $experiment->getRowID();
        }
        if ($row_id !== null)
            $this->_row_id = intval($row_id);
    }

    /**
     * Get the Row ID associated with this experiment (usually for database use).
     *
     * @return int The stored Row ID for this record, or null.
     */
    public function getRowID() {
        return $this->_row_id;
    }

    /**
     * Set the Row ID associated with this experiment (usually for database use).
     *
     * @param int $val
     */
    public function setRowId($val) {
        if ($val === null) {
            $this->_row_id = null;
        } else if (is_int($val)) {
            $this->_row_id = intval($val);
        } else {
            /**
             * @see Scenario_Exception
             */
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('$val must be null or an integer value.');
        }
    }

    /**
     * Get a treatment for this experiment for the given Identity.
     * 
     * If the treatment does not exist, this method invokes getNewTreatment, 
     * which may be overridden in subclasses to return treatments according to 
     * different lists and weights. getTreatment should always return "default" 
     * among its options.
     *
     * @param Scenario_Identity $id Identity to use in retrieving a new or existing treatment.
     * @param bool $create (Optional) whether or not to create the treatment if it does not exist.
     * @return Scenario_Treatment The requested treatment object.
     */
    public function getTreatment(Scenario_Identity $id, $create = true) {
        $adapter = $this->getCore()->getAdapter();
        $treatment = $adapter->GetTreatmentForIdentity($this, $id);
        if ($treatment == null && $create) { // no treatment was stored in data adapter
            // get a new treatment and set it in the adapter
            $treatment = $this->getNewTreatment();
            $result = $adapter->SetTreatment($this, $treatment, $id);
        }
        return $treatment;
    }

    /**
     * Retrieves a new Scenario_Treatment object for this experiment.
     *
     * Should randomize between whatever treatments are desired for the experiment.
     * By default, treatments select "default" or "alternate" if supplied with null for their name.
     *
     * @return Scenario_Treatment
     */
    public function getNewTreatment() {
        require_once 'Scenario/Treatment.php';
        $treatment = new Scenario_Treatment(null, $this);
        return $treatment;
    }

    /**
     * Get the experiment's identifying name.
     *
     * @return string
     */
    public function getExperimentID() {
        return $this->_experiment_id;
    }

    /**
     * Set the experiment's identifying name.
     *
     * @param string $experiment_id
     */
    public function setExperimentID($experiment_id) {
        $this->_experiment_id = $experiment_id;
    }

    /**
     * Get the results of this experiment as a Scenario_ResultSet collection.
     *
     * @return Scenario_ResultSet
     */
    public function getResults() {
        return $this->getCore()->getAdapter()->GetResults($this);
    }

    /**
     * Gets a safe Scenario_Core reference.
     * 
     * @todo Pass the core reference to new experiment objects rather than use the singleton.
     * @return Scenario_Core An instance of Scenario_Core appropriate for use in the context of this experiment.
     */
    public function getCore() {
        require_once 'Scenario/Core.php';
        return Scenario_Core::getInstance();
    }

}