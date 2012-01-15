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
 * Result consolidator
 *
 * Consolidates result data in a memory-efficient manner.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011-2012 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Data_Consolidator {
	
	private $_queue;
	private $_results;
	private $_core;
	
	public function __construct($params) {
	   
	   if (is_array($params) && array_key_exists('core', $params)) 
	      $this->_core = $params['core'];
		
		$this->_queue = array();
		$this->_results = array();
		$this->_parent_experiments = array();
		
	}
	
	private $_parent_experiments;
	
	private function has_full_resultset($parent, $userid) {
	   
	   if (!array_key_exists($parent, $this->_queue)) return FALSE;
	   if (!array_key_exists($userid, $this->_queue[$parent])) return FALSE;
	   $working =& $this->_queue[$parent][$userid];
	   
	   if (!array_key_exists($parent, $this->_parent_experiments)) {
	      $this->_parent_experiments[$parent] = $this->getCore()->getExperiment($parent);
	   }
	   
	   foreach($this->_parent_experiments[$parent]->getChildren() as $child) {
	      if (!array_key_exists($child->getExperimentID(), $working)) return FALSE;
	   }
	   
	   return TRUE;
	}

	/*
	normal:
	results[expname][_multivar,_treatments[tname][completed,total]]
	multivar:
	results[pname][_multivar,_subexperiments[_treatments[tname][completed,total]]]
	*/
	
	private function queue_result(Scenario_Result $result) {
	   
	   if (!array_key_exists($result->parent_name, $this->_queue)) {
	      $this->_queue[$result->parent_name] = array();
	   }
	   $queue =& $this->_queue[$result->parent_name];
	   
	   if (!array_key_exists($result->user_id, $queue)) {
	      $queue[$result->user_id] = array(
	         $result->experiment_name => $result
	      );
	   } else {
	      $queue[$result->user_id][$result->experiment_name] = $result;
	   }
	   
	}
	
	private function get_subexperiment_order($parent) {

	   if (!array_key_exists($parent, $this->_parent_experiments)) {
	      $this->_parent_experiments[$parent] = $this->getCore()->getExperiment($parent);
	   }
	   $output = array();
	   foreach($this->_parent_experiments[$parent]->getChildren() as $child) {
	      $output[] = $child->getExperimentID();
	   }
	   sort($output);
	   return $output;
	}
	
	public function addResult(Scenario_Result $result) {
		if ($result->parent_id != null) {
		   
		   // push result into queue by identifier
		   // if identifier has a full set, process it
		   
		   $this->queue_result($result);
		   
		   if ($this->has_full_resultset($result->parent_name, $result->user_id)) {
		      // push the results into the result data
		      if (!array_key_exists($result->parent_name, $this->_results)) {
		         $this->_results[$result->parent_name] = array(
		            '_multivar' => TRUE,
		            '_treatments' => array(),
		            '_order' => $this->get_subexperiment_order($result->parent_name)
		         );
		      }
		      
		      $treatments =& $this->_results[$result->parent_name]['_treatments'];
		      $order =& $this->_results[$result->parent_name]['_order'];
		      $resultset =& $this->_queue[$result->parent_name][$result->user_id];
		      
		      $treatment_list = array();
		      foreach($order as $subexp) {
		         $treatment_list[] = $resultset[$subexp]->treatment_name;
		      }
		      $treatment_id = implode(':', $treatment_list);
		      
		      if (!array_key_exists($treatment_id, $treatments)) {
		         $treatments[$treatment_id] = array(
		            'completed' => 0, 'total' => 0
		         );
		      }
		      
		      $treatments[$treatment_id]['total']++;
		      if ($result->completed) 
		         $treatments[$treatment_id]['completed']++;
		      
		      unset($this->_queue[$result->parent_name][$result->user_id]);
		      
		   }
		   
			// multivariate result
		} else {
			if (!array_key_exists($result->experiment_name, $this->_results)) {
				$this->_results[$result->experiment_name] = array(
				  '_multivar' => FALSE,
				  '_treatments' => array()
				);
			}
			$target = &$this->_results[$result->experiment_name]['_treatments'];
			if (!array_key_exists($result->treatment_name, $target)) {
				$target[$result->treatment_name] = array(
				  'completed' => 0, 'total' => 0
				);
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
	
	public function getCore() {
	   if ($this->_core != null) {
	      return $this->_core;
	   }
	   return Scenario_Core::getInstance();
	}
	
}