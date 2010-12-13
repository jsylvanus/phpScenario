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
 * Data Analyzer
 *
 * Provides statistical analysis information for experiments.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Data_Analyzer {
	
	protected $_analysis;
	protected $_overall_analysis;
	protected $_rawData;
	
	protected $_lastExperiment;
	
	/**
	 * Core reference
	 * 
	 * @var Scenario_Core
	 */
	protected $_core;
	
	public function __get($name) {
		if ($name == 'results') return $this->_rawData;
		return null;
	}
	
	public function __construct($core, $experiment = null) {
		$this->_core = $core;
		if ($experiment !== null) {
			$this->analyzeExperiment($experiment);
		}
	}
	
	public function analyzeExperiment($experiment) {
		$this->_lastExperiment = $experiment;
		$this->_analysis = array();
		
		// $parts = $experiment->getParts();
		
		// getParts should return an array of variants, treating all experiments
		// as multivariates. a simple a/b test just returns array('default')
		try {
			$this->_rawData = $this->_loadResults();
//			foreach ($parts as $value) {
//				$this->_analysis[$value] = $this->_analyzeSection($value);
//			} // set the analysis keys
//			$this->_overall_analysis = $this->_summarize();
		} catch (Scenario_Data_Exception $e) {
			require_once 'Scenario/Exception.php';
			throw new Scenario_Exception('A data exception occurred while processing results', 0, $e);
		} catch (Scenario_Exception $e) {
			throw new Exception('A general Scenario exception occurred while processing results', 0, $e);
		} catch (Exception $e) {
			// well, shit.
			throw $e;
		}
	}
	
	private function _loadResults() {
		if ($this->_lastExperiment === null) {
			require_once 'Scenario/Exception.php';
			throw new Scenario_Exception('_analyzeSection called while _lastExperiment is null');
		}
		$exp = $this->_lastExperiment;
		
		$data = array(
            'results' => array(),
            'total_records' => 0
        );
		
		// get data in chunks
		$limit = 1000;
		$start = 0;
		do {
			$results = $this->_core->getAdapter()->GetResults($exp, $start, $limit);
			$start += $limit;
			foreach($results as $result) {
				if ($result instanceof Scenario_Result) {
					$expid = $result->getExperiment()->getExperimentID();
					// TODO: parent id handling here for multivariate (nested experiments)
					
					$treatment = $result->getTreatment()->getName();
					if (!array_key_exists($expid, $data['results'])) {
						$data['results'][$expid] = array(
							'_total' => 0
						);
					}
					if (!array_key_exists($treatment, $data['results'][$expid])) {
						$data['results'][$expid][$treatment] = array(
							'total' => 0,
							'completed' => 0,
						);
					}
					$data['results'][$expid][$treatment]['total']++;
					if ($result->isCompleted())
						$data['results'][$expid][$treatment]['completed']++;
					$data['total_records']++;
					$data['results'][$expid]['_total']++;
				} else {
					require_once 'Scenario/Exception.php';
					throw new Scenario_Exception('Scenario_ResultSet must contain only Scenario_Result objects.');
				}
			}
		} while (count($results) >= $limit);
		
		return $data;
	}
	
	private function _summarize() {
		
	}
	
}