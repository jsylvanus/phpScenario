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
 * Data Analyzer
 *
 * Provides statistical analysis information for experiments.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011-2012 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Data_Analyzer {
	
	/**
	 *
	 * @var array
	 */
	protected $_analysis;
	
	public function getResults($analysis_only = true, $for_expname = null) {
		if ($for_expname == null) {
			$out = array();
			foreach($this->_analysis as $tname => $results) {
				$out[$tname] = $analysis_only ? $results['analysis'] : $results;
			}
			return $out;
		} 
		return $analysis_only ? 
			$this->_analysis[$for_expname]['analysis'] : 
			$this->_analysis[$for_expname];
	}
	
	/**
	 *
	 * @var Scenario_Data_Consolidator
	 */
	protected $_rawData;
	
	/**
	 *
	 * @var Scenario_Experiment
	 */
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
		
		try {
			$this->_rawData = $this->_loadResults();
			$this->_analysis = $this->_summarize();
		} catch (Scenario_Data_Exception $e) {
			require_once 'Scenario/Exception.php';
			throw new Scenario_Exception('A data exception occurred while processing results: ' . $e->getMessage());
		} catch (Scenario_Exception $e) {
			throw new Exception('A general Scenario exception occurred while processing results: ' . $e->getMessage());
		} catch (Exception $e) {
			// well, shit.
			throw $e;
		}
	}
	
	public function getLastExperiment() {
		return $this->_lastExperiment;
	}
	
	/**
	 * 
	 * @return Scenario_Data_Consolidator 
	 */
	private function _loadResults() {
		if ($this->_lastExperiment === null) {
			require_once 'Scenario/Exception.php';
			throw new Scenario_Exception('_loadResults called while _lastExperiment is null');
		}
		$exp = $this->_lastExperiment;
		
		require_once 'Scenario/Data/Consolidator.php';
		$data = new Scenario_Data_Consolidator(array());
		
		// get data in chunks
		$limit = 1000;
		$start = 0;
		$numresults = 0;
		do {
			$results = $this->_core->getAdapter()->GetResults($exp, $start, $limit);
			$numresults = count($results);
			$start += $limit;
			foreach($results as $result) {
			   
				if ($result instanceof Scenario_Result) {
					$data->addResult($result);
				} else {
					require_once 'Scenario/Exception.php';
					throw new Scenario_Exception('Scenario_ResultSet must contain only Scenario_Result objects.');
				}
			}
			unset($results);
		} while ($numresults >= $limit);
				
		return $data;
	}
	
	private function _summarize() {
		
		$results = array();
		// string array
		foreach($this->_rawData->getExperimentNames() as $expName) {
			// raw data array
			$data = $this->_rawData->getExperimentData($expName);
			
			$data['analysis'] = array(
				'total_tested'		=> 0,
				'total_converted'	=> 0,
				'conversion_rate'	=> 0.0,
				'treatments'		=> array()
			);
			$analysis = &$data['analysis'];
			$treatments = &$analysis['treatments'];
			
         // if (!$data['_multivar']) {
				
				// first pass: totals & per-treatment c-rates
				foreach($data['_treatments'] as $tname => $vals) {
					$analysis['total_tested'] += $treatments[$tname]['total_tested'] = $vals['total'];
					$analysis['total_converted'] += $treatments[$tname]['total_converted'] = $vals['completed'];
					$treatments[$tname]['conversion_rate'] = floatval($vals['completed']) / floatval($vals['total']);
				}
				$analysis['conversion_rate'] = floatval($analysis['total_converted']) / floatval($analysis['total_tested']);
				// second pass: per-treatment calculations
				foreach($data['_treatments'] as $tname => $vals) {
					
					// percent tested
					$pct = $treatments[$tname]['percent_tested'] = $vals['total'] / floatval($analysis['total_tested']);
					// standard error
					$stderr = $treatments[$tname]['standard_error'] = sqrt( ($pct * ( 1 - $pct )) / floatval($vals['total']) );
					// 95% confidence interval
					$treatments[$tname]['high_confidence'] = $stderr * 1.96;
					// z-score
					$cRate = $analysis['conversion_rate'];
					$cTotal = $analysis['total_tested'];
					$tRate = $treatments[$tname]['conversion_rate'];
					$tTotal = $vals['total'];
					if ($cTotal == 0 || $tTotal == 0 || $tRate == 0) {
					   $treatments[$tname]['z_score'] = 0;
					} else {
					   $treatments[$tname]['z_score'] = ($tRate - $cRate) / sqrt( ( ($tRate * (1.0 - $tRate)) / floatval($tTotal)) + ( ($cRate * (1.0 - $cRate)) / floatval($cTotal)) );;
				   }
				}
         // 
         // } else {
         //    
         //    // multivar version
         //    var_dump($this->_rawData);
         // }
         // 
			$results[$expName] = $data;
		}
		$this->_analysis = $results;
		return $results;
		/*
		 * Stuff to calculate:
		 * 1. total results
		 * 2. conversion rates (treatment conv / treatment results; per-treatment)
		 * 3. % tested (# tested / total results; per-treatment)
		 * 4. Standard error ( Sqrt( (#3 * (1 - #3)) / # tested in treatment ); per treatment )
		 * 5. 95% confidence level ( #4 * 1.96, split across #2; per-treatment )
		 * 6. z-score
		 */
	}
	
	/**
	 *
	 * @param Scenario_Experiment $experiment
	 * @return array 
	 */
	private function _treatmentMatrix(Scenario_Experiment $experiment) {
		if ($experiment->isMultiVar()) {
			$tmp = array();
			foreach($experiment->getChildren() as $cxp) {
				/* @var $cxp Scenario_Experiment */
				$tmp[$cxp->getExperimentID()] = array_keys($experiment->getWeightings());
			}
			if (count($tmp) == 0) return array();
			ksort($tmp);
			$out = array();
			foreach($tmp[0] as $val) {
				$out[] = array($val);
			}
			if (count($tmp) == 1) return $out;
			for($i = 1; $i < count($order); $i++) {
				$old = $out;
				$out = array();
				foreach($tmp[$i] as $right) {
					foreach($old as $left) {
						$out[] = array_merge($left, array($right));
					}
				}
			}
			return $out;
		} else {
			$tmp = array_keys($experiment->getWeightings());
			$out = array();
			foreach($tmp as $k) $out[] = array($k);
			return $out;
		}
	}
	
}