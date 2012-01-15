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
 * Experiment class
 *
 * Handles experiment-related functions, stores experiment data.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011-2012 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Experiment {

	/**
	 * Data analyzer for the experiment, created by calling getResults
	 * 
	 * @var Scenario_Data_Analyzer
	 */
	protected $_analyzer;
	
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
	 * Child experiments indexed by name (MultiVar)
	 * 
	 * @var array
	 */
	protected $_children;
	
	/**
	 * Parent experiment (means this is a child of a multivar experiment)
	 * 
	 * @var Scenario_Experiment
	 */
	protected $_parent;
	
	/**
	 * Array of possible treatments and their weights
	 * 
	 * @var array
	 */
	protected $_weights;
	
	/**
	 * String array of possible treatments
	 * 
	 * @var array
	 */
	protected $_treatments;
	
	/**
	 * Control name (usually 'default')
	 * 
	 * @var string 
	 */
	protected $_control;

	/**
     * Construct a new Experiment object.
     *
     * @param string $experiment_id
     * @param int|null $row_id
     * @param bool $create
	 * @param array $options
     */
    public function __construct($experiment_id, $row_id = null, $create = true, $options = array()) {
		if (is_array($options) && array_key_exists('parent', $options)) {
			$this->_parent = $options['parent'];
		}
        if (is_array($options) && array_key_exists('children', $options)) {
			$this->_children = $options['children'];
			if (count($this->_children) > 0) {
			   foreach($this->_children as $child) {
					$child->setParent($this);
				}
			}
		}
		if (is_array($options) && array_key_exists('weightings', $options)) {
			$this->_weights = $options['weightings'];
			$this->_treatments = array_keys($options['weightings']);
		} else if (is_array($options) && array_key_exists('treatments', $options)) {
			$this->_treatments = $options['treatments'];
			$this->_weights = array();
			foreach($this->_treatments as $value) {
				$this->_weights[$value] = 50;
			}
		} else {
			$this->_weights = array('default'=>50, 'alternate'=>50);
			$this->_treatments = array('default','alternate');
		}
		$this->_control = (is_array($options) && array_key_exists('control', $options)) ? $options['control'] : 'default';
		$this->setExperimentID($experiment_id);
        if ($row_id == null) {
            $experiment = $this->getCore()->getAdapter()->GetExperiment($experiment_id);
            if ($experiment === null && $create) {
                $experiment = $this->getCore()->getAdapter()->AddExperiment($experiment_id, 
					array('weightings'=>$this->_weights, 'control'=>$this->_control),
					$this->getParent()
				);
            }
            if ($experiment !== null)
                $row_id = $experiment->getRowID();
        }
        if ($row_id !== null)
            $this->_row_id = intval($row_id);
    }
	
	/**
	 *
	 * @return array data to be serialized
	 */
	public function getData() {
		$data = array();
		if ($this->isChild()) {
			$data['parent'] = $this->_parent->getExperimentID();
		}
		if ($this->isMultiVar()) {
			$data['children'] = array_keys($this->_children);
		}
		$data['weightings'] = $this->getWeightings();
		return $data;
	}

	/**
	 * Determine whether this is a child of a multivar experiment (it has a parent)
	 * 
	 * @return boolean
	 */
	public function isChild() {
		return $this->_parent !== null;
	}
	
	/**
	 * Determine whether this is a multivar experiment (parent) or not.
	 * 
	 * @return boolean 
	 */
	public function isMultiVar() {
		return $this->_children !== null && $this->_parent === null;
	}
	
	/**
	 * Returns the parent experiment if this is a child of a multivariant experiment. 
	 * 
	 * @return Scenario_Experiment Parent experiment or null.
	 */
	public function getParent() {
		return $this->_parent;
	}
	
	public function setParent($parent) {
		if ($parent instanceof Scenario_Experiment) {
			$this->_parent = $parent;
		} else if (is_string($parent)) {
			$this->_parent = $this->getCore()->getExperiment($parent);
		} else {
			require_once 'Scenario/Exception.php';
			throw new Scenario_Exception('$parent must be a string or instance of Scenario_Experiment');
		}
	}
	
	/**
	 * Get child experiments of a multivariant experiment.
	 * 
	 * @return array
	 */
	public function getChildren() {
		return $this->_children;
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
		if ($this->isMultiVar()) {
			$multiTreatment = array();
			foreach ($this->_children as $child) {
				$multiTreatment[$child->getExperimentID()] = $child->getTreatment($id, $create);
			}
			return $multiTreatment;
		}
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
	 * Set multivariate info.
	 * 
	 * @param array $variants 
	 */
	public function setMultiVars($variants = null) {
		
		if (is_array($variants)) {
			$variants = self::FixMultivariateArray($variants);
			foreach($variants as $k => $v) {
				$this->ensureVariant($k, $v);
			}
		}
		
	}
	
	/**
	 * Ensures that a sub-experiment (or variant) is registered with this parent experiment.
	 * 
	 * If the sub-experiment does not exist, it is created and added to $this->_children.
	 * Also verifies that weightings match and overwrites them if not.
	 * 
	 * @param string $k
	 * @param array $v
	 * @return null
	 */
	public function ensureVariant($k, $v) {
		if (!is_array($this->_children)) $this->_children = array();
		
		// key doesn't exist, so create it
		if (!array_key_exists($k, $this->_children)) {
			$newExp = new Scenario_Experiment($k, null, true, array(
				'parent' => $this, 
				'treatments' => array_merge( array_keys($v['control']), array_keys($v['alternates']) ),
				'weightings' => array_merge( $v['control'], $v['alternates'] )
			));
			$this->_children[$k] = $newExp;
			return;
		}
		
		// it exists already, so make sure it matches
		$chk = $this->_children[$k];
		$chkWeights = array_merge($v['control'], $v['alternates']);
		foreach($chk->getWeightings() as $trt => $w) {
			if ($chkWeights[$trt] == $w) continue;
			$chk->setWeight($trt, $chkWeights[$trt]);
		}
	}
	
	/**
	 * Retrieve the weightings array.
	 * 
	 * Weightings are stored using their treatment names as keys.
	 * 
	 * @return array
	 */
	public function getWeightings() {
		return $this->_weights;
	}
	
	/**
	 * Set a treatment weight.
	 * 
	 * @param string $treatment
	 * @param int $weight 
	 */
	public function setWeight($treatment, $weight) {
		if (!is_array($this->_treatments)) {
			$this->_treatments = array();
		}
		if (!in_array($treatment, $this->_treatments)) {
			$this->_treatments[] = $treatment;
		}
		if (!is_array($this->_weights)) {
			$this->_weights = array();
		}
		$this->_weights[$treatment] = $weight;
		if ($this->getRowID() !== null) {
			// update the data entry in DB
		}
	}
	
	/**
	 * Takes the array supplied for multivariate settings and expands it.
	 * 
	 * Expansion is based on the following rules:
	 * 
	 * 1. An array of strings is expanded to an array of string => true
	 * 2. A boolean (true OR false) is translated to a simple 50/50 control/alternate test
	 * 3. A sub-test, if a string array, is treated as a list of alternates
	 * 4. If weights are not specified (as in #3), they are assumed to be 50
	 * 
	 * @param array $mv
	 * @return array
	 */
	public static function FixMultivariateArray($mv) {
		
		if (self::_isStringArray($mv)) {
			$theTruth = array();
			for($i = 0; $i < count($mv); $i++) {
				$theTruth[] = true;
			}
			$mv = array_combine($mv, $theTruth);
		}
		
		foreach ($mv as $k => $var) {
			// convert from boolean
			if (is_bool($var)) {
				$mv[$k] = $var = array('control' => array('default' => 50), 'alternates' => array('alternate' => 50));
				continue;
			}
			
			if (is_array($var)) {
				
				$keys = array_keys($var);
				if (self::_isStringArray($var)) {
					// first value in the array is not an array, assume string list
					$out = array();
					foreach(array_values($var) as $name) {
						$out[$name] = 50;
					}
					$mv[$k] = $var = array('control' => array('default' => 50), 'alternates' => $out);
					continue;
				}
				
				// at this point we've ruled out bool & string array, so top level structure is assumed correct
				
				if (!array_key_exists('control', $var)) {
					// ensure control exists
					$mv[$k]['control'] = $var['control'] = array('default' => 50);
				}
				
				foreach($var as $k2 => $v2) {
					if (self::_isStringArray($v2)) {
						$mv[$k][$k2] = $var[$k2] = array_combine($v2, self::_blankWeightArray(count($v2)));
					}
				}
				
			}
		}
		
		return $mv;
	}
	
	/**
	 * 
	 * @param array $arr The array to test
	 * @return boolean Whether the array contains strings
	 */
	private static function _isStringArray($arr) {
		foreach ($arr as $v) {
			if (is_string($v))
				return true;
		}
		return false;
	}
	
	/**
	 * Creates an array of default weights (50) to match a key list.
	 * 
	 * @param int $len
	 * @return array 
	 */
	private static function _blankWeightArray($len) {
		$out = array();
		for($i = 0; $i < $len; $i++)
			$out[] = 50;
		return $out;
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
		$treatment = $this->weightedRandomTreatment();
		$treatment_obj = new Scenario_Treatment($treatment, $this);
        return $treatment_obj;
    }
	
	/**
	 * Retrieves a treatment name using weighted values.
	 * 
	 * @return string
	 */
	private function weightedRandomTreatment() {
		$weights = $this->getWeightings();
		$max = array_sum($weights);
		$soFar = 0;
		$random = mt_rand(0, $max - 1);
		foreach($weights as $k => $v) {
			$soFar += $v;
			if ($random < $soFar) {
				return $k;
			}
		}
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
    public function getResults($analysis_only = true) {
			return $this->getCore()->getAdapter()->GetResults($this, 0, 100000);
    }
		
		public function getAnalysis($analysis_only = true) {
			$analyzer = $this->getAnalyzer();
			return $analyzer->getResults($analysis_only, $this->getExperimentID());
		}

		public function getAnalyzer() {
			if ($this->_analyzer == null) {
				$this->_analyzer = new Scenario_Data_Analyzer($this->getCore(), $this);
			}
			return $this->_analyzer;
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
    
    public function finish(Scenario_Identity $id) {
       return $this->getCore()->getAdapter()->FinishExperiment($this, $id);
    }

}