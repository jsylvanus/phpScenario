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
 * @copyright  Copyright (c) 2011-2012 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */


/**
 * Result object
 *
 * Essentially a storage object for all data related to a given result.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011-2012 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Result {
	
	public $user_id;
	public $completed;
	public $treatment_id;
	public $treatment_name;
	public $experiment_name;
	public $experiment_id;
	public $experiment_data;
	public $parent_id;
	public $parent_name;
	public $parent_data;
	
	protected $_experiment;
	protected $_parent;
	protected $_identity;
	protected $_treatment;
	
	private $_core;
	
    public function __construct($userid, $completed = null, $treatmentid = null, $treatmentname = null, 
			$experimentid = null, $experimentname = null, $experimentdata = null, $parentid = null, 
			$parentname = null, $parentdata = null, $core = null) 
	{
		$loadme = array();
		if (!is_array($userid)) {
			$loadme = array(
				'user_id' => $userid,
				'completed' => $completed,
				'treatment_id' => $treatmentid,
				'treatment_name' => $treatmentname,
				'experiment_id' => $experimentid,
				'experiment_name' => $experimentname,
				'experiment_data' => $experimentdata,
				'parent_id' => $parentid,
				'parent_name' => $parentname,
				'parent_data' => $parentdata
			);
			$this->setCore($core);
		} else {
			if (array_key_exists('core', $userid)) {
				$this->setCore($userid['core']);
				unset($userid['core']);
			} else if ($completed instanceof Scenario_Core) {
				$this->setCore($completed);
			}
			$loadme = $userid;
		}
		$this->user_id = $loadme['user_id'];
		$this->completed = ($loadme['completed'] == '1');
		$this->treatment_id = $loadme['treatment_id'];
		$this->treatment_name = $loadme['treatment_name'];
		$this->experiment_id = $loadme['experiment_id'];
		$this->experiment_name = $loadme['experiment_name'];
		$this->experiment_data = is_string($loadme['experiment_data']) ? unserialize($loadme['experiment_data']) : $loadme['experiment_data']; 
		if ($loadme['parent_id'] != null) {
			$this->parent_id = $loadme['parent_id'];
			$this->parent_name = $loadme['parent_name'];
			$this->parent_data = is_string($loadme['parent_data']) ? unserialize($loadme['parent_data']) : $loadme['parent_data'];
		}
    }

    /**
     *
     * @return bool Whether the treatment was completed or not.
     */
    public function isCompleted() {
        return $this->completed;
    }

	/**
	 *
	 * @return bool Whether the treatment is attached to a parent experiment or not.
	 */
	public function isMultivar() {
		return $this->_parent != null;
	}
	
    /**
     * Gets the associated experiment, optionally resolving it to an object if it's a string.
     *
     * @return Scenario_Experiment The experiment associated with this result.
     */
    public function getExperiment() {
        if ($this->_experiment === null) {
			$this->_experiment = new Scenario_Experiment(
				$this->experiment_name, $this->experiment_id, 
				false, $this->experiment_data
			);
			if ($this->parent_id !== null) {
				$this->_experiment->setParent($this->getParent());
			}
		}
		return $this->_experiment;
    }

    /**
     * Returns the treatment associated with this result.
     *
     * @return Scenario_Treatment
     */
    public function getTreatment() {
        if ($this->_treatment === null) {
			$this->_treatment = new Scenario_Treatment(
				$this->treatment_name, $this->getExperiment(), $this->treatment_id
			);
		}
		return $this->_treatment;
    }
	
	public function getParent() {
		if ($this->parent_id === null) 
			return null;
		if ($this->_parent === null) {
			$this->_parent = new Scenario_Experiment(
				$this->parent_name, 
				$this->parent_id, 
				false, 
				array_merge($this->parent_data, array('children' => array($this->getExperiment())))
			);
		}
		return $this->_parent;
	}

    /**
     * Returns the identity associated with this result.
     *
     * @return Scenario_Identity
     */
    public function getIdentity() {
		if ($this->_identity === null) {
			$this->_identity = new Scenario_Identity($this->user_id);
		}
        return $this->_identity;
    }

    /**
     * Gets the core reference appropriate to this instance.
     *
     * @return Scenario_Core
     * @todo Use a reference to the core rather than the singleton.
     */
    public function getCore($default_to_singleton = true) {
		if ($this->_core == null && $default_to_singleton)
			return Scenario_Core::getInstance();
		return $this->_core;
    }
	
	/**
	 *
	 * @param Scenario_Core $core 
	 */
	public function setCore($core) {
		if ($core instanceof Scenario_Core) {
			$this->_core = $core;
		} else if ($core === null) {
			$this->_core = null;
		} else {
			require_once 'Scenario/Exception.php';
			throw new Scenario_Exception('Core cannot be set to a non-core object');
		}
	}

}
