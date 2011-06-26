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
 * @copyright  Copyright (c) 2011 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */

/**
 * Result consolidator
 *
 * Consolidates result data in a memory-efficient manner.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Data_Consolidator {
	
	private $_queue;
	private $_results;
	
	public function __construct($params) {
		$this->_queue = array();
		$this->_results = array();
	}
	
	public function addResult(Scenario_Result $result) {
		if ($result->parent_id != null) {
			// multivariate result
		} else {
			if (!array_key_exists($result->experiment_name, $this->_results)) {
				$this->_results[$result->experiment_name] = array();
				$this->_results[$result->experiment_name]['_multivar'] = false;
				$this->_results[$result->experiment_name]['_treatments'] = array();
			}
			$target = &$this->_results[$result->experiment_name]['_treatments'];
			if (!array_key_exists($result->treatment_name, $target)) {
				$target[$result->treatment_name] = array();
				$target[$result->treatment_name]['completed'] = 0;
				$target[$result->treatment_name]['total'] = 0;
			}
			$target[$result->treatment_name]['total']++;
			if ($result->isCompleted())
				$target[$result->treatment_name]['completed']++;
		}
	}
	
	public function addResults(Scenario_ResultSet $results) {
		foreach($results as $result) {
			$this->addResult($result);
		}
	}
	
	public function getExperimentNames() {
		if (!empty($this->_results)) {
			return array_keys($this->_results);
		} else {
			throw new Scenario_Exception('You must add results before you can call this function');
		}
	}
	
	public function getExperimentData($name) {
		return $this->_results[$name];
	}
	
}