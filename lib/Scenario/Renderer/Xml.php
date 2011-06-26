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
 * @see Scenario_Renderer_Abstract
 */
require_once 'Scenario/Renderer/Abstract.php';

/**
 * XML / XSLT resultset renderer.
 *
 * Renderer class that outputs in either raw XML or translates it using either
 * a supplied or built in XSL file. Currently supported XSL parameters: 'html'.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Renderer_Xml extends Scenario_Renderer_Abstract {

    const AS_HTML = 'html';
    const AS_XML = 'xml';
	
	/**
	 *
	 * @var Scenario_Core
	 */
	private $_core;
	
	public function getCore() {
		return $this->_core;
	}

    /**
     *
     * @param string $stylesheet
	 * @param Scenario_Core $core
     */
    public function __construct($stylesheet = self::AS_XML, $core = null) { // see also: html
        $this->setTranslator($stylesheet);
		if ($core == null) {
			$this->_core = Scenario_Core::getInstance();
		}
    }

    /**
     * Sets the xslt parameter
     *
     * Alias for setParam('xslt',$translator). If the translator is an instance
     * of DOMDocument it will be used in the XML translation by itself. 
     * If $translator starts with a slash, it will be interpreted as a path.
     * Otherwise, the file is assumed to reside within the Scenario/Reporter/Xslt/
     * folder, as {$translator}.xsl
     *
     * @param mixed $name Can be set to either a string or a DOMDocument (XSL)
     */
    public function setTranslator($translator) {

        if (is_string($translator) || $translator instanceof DOMDocument) {
            $this->setParam('xslt', $translator);
            return;
        }

        require_once 'Scenario/Exception.php';
        throw new Scenario_Exception('Translator must be a string or instance of DOMDocument.');
    }

    /**
     * Returns the current translator (DOMDocument or string)
     *
     * @return mixed
     */
    public function getTranslator() {
        return $this->getParam('xslt');
    }

    /**
     * Render a Scenario_ResultSet
     *
     * Renders a result set in XML. To render multiple experiments, merge the
     * collections, or render them individually.
     *
	 * @deprecated
     * @param Scenario_ResultSet $results   The resultset to render.
     * @param bool $capture     (optional) Whether to capture the results rather than render them to output.
     * @return string   The rendered result document as a string.
     */
    public function renderSet(Scenario_ResultSet $results, $capture = false) {
		
        $results = $this->resultsAsXml($this->resultSetToAnalyzerArray($results));

        if ($this->getTranslator() == self::AS_XML) {
            $output = $results->saveXML();
            if (!$capture)
                echo $output;
            return $output;
        }

        $xsl = $this->getTranslator();
        if (is_string($xsl))
            $xsl = $this->getXslt($xsl);

        if (!($xsl instanceof DOMDocument)) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Translator must be set to a string or an instance of DOMDocument.');
        }

        $output = $this->translate($results, $xsl);
        
        if (!$capture)
            echo $output;

        return $output;
    }
	
	private function resultSetToAnalyzerArray(Scenario_ResultSet $resultSet) {
		$analyzers = array();
		$experiments = $this->getExperimentsFromResultSet($resultSet);
		
		/** @see Scenario_Data_Analyzer */
		require_once 'Scenario/Data/Analyzer.php';
		
		foreach($experiments as $exp) {
			$analyzers[] = new Scenario_Data_Analyzer($this->getCore(), $exp);
		}
		
		return $analyzers;
	}
	
	private function getExperimentsFromResultSet(Scenario_ResultSet $results) {
		$names = array();
		$experiments = array();
		foreach($results as $result) {
			$test = $result->isMultivar() ? $result->getParent() : $result->getExperiment();
			$id = $test->getExperimentID();
			if (!in_array($id, $names)) {
				$names[] = $id;
				$experiments[$id] = $test;
			}
		}
		return $experiments;
	}

    /**
     * Translates the provided xml and xsl DOMDocuments to string
     *
     * @param DOMDocument $xml XML to use in the translation.
     * @param DOMDocument $xslt XSL Stylesheet to apply to the XML.
     * @return string Output of the translation.
     */
    public function translate(DOMDocument $xml, DOMDocument $xslt) {
        $renderer = new XSLTProcessor;
        $renderer->importStylesheet($xslt);
        $output = $renderer->transformToXml($xml);

        return $output;
    }

    /**
     * Translates a result or resultset into an XML DOMDocument.
     *
     * @param Scenario_Data_Analyzer|array $analyzers
     * @return DOMDocument
     */
    public function resultsAsXml($analyzers) {
			
			if ($analyzers instanceof Scenario_ResultSet) {
				$analyzers = $this->resultSetToAnalyzerArray($analyzers);
			} else if (!is_array($analyzers)) {
				$lastExp = $analyzers->getLastExperiment();
				if ($lastExp != null) {
					$expname = $lastExp->getExperimentID();
					$analyzers = array($expname => $analyzers);
				} else {
					require_once 'Scenario/Exception.php';
					throw new Scenario_Exception('Analyzer provided had not analyzed anything.');
				}
			}

			
			$xml = new XMLWriter();
			$xml->openMemory();

			$xml->startElement('experimentresults'); // root
			// $xml->writeAttribute('totalresults', $data['totalRecords']); // no longer gathered across all experiments

			foreach($analyzers as $idx => $analyzer) {

				$results = $analyzer->getResults(false);
				foreach($results as $expname => $analysis) {
					// $results = $results[$expname];

					$xml->startElement('experiment');
					$xml->writeAttribute('name', $expname);
					if ($analysis['_multivar']) {
						$xml->writeAttribute('multivar', 'multivar');
						// sub-experiment names?
					}

					$stats = $analysis['analysis'];
					$xml->writeAttribute('total', $stats['total_tested']);
					$xml->writeAttribute('converted', $stats['total_converted']);
					$xml->writeAttribute('conversionrate', $stats['conversion_rate']);

					foreach($stats['treatments'] as $tname => $treatment) {
						$xml->startElement('treatment');
						$xml->writeAttribute('name', $tname);
						$xml->writeAttribute('total', $treatment['total_tested']);
						$xml->writeAttribute('converted', $treatment['total_converted']);
						$xml->writeAttribute('conversionrate', $treatment['conversion_rate']);
						$xml->writeAttribute('percenttested', $treatment['percent_tested']);
						$xml->writeAttribute('stderror', $treatment['standard_error']);
						$xml->writeAttribute('highconfidence', $treatment['high_confidence']);
						if ($tname != 'default') {
							$xml->writeAttribute('zscore', $treatment['z_score']);
						} else {
							$xml->writeAttribute('control', 'control');
						}
						$xml->endElement(); // treatment
					}
					$xml->endElement(); // experiment
					
				}
			}

			$xml->endElement(); // experimentresults (root)

			$dom = new DOMDocument;
			$dom->loadXML($xml->outputMemory());
			return $dom;
    }
	
		private function _xmlSingleTag($xml, $tagname, $atts) {
			$xml->startElement($tagname);
			foreach($atts as $att => $val) {
				$xml->writeAttribute($att, $val);
			}
			$xml->endElement();
		}

    /**
     * Load an XSL file from Xslt subdir.
     *
     * @param string $name Name of the XSL file to load. Should be a filename within Scenario/Renderer/Xslt/
     * @return DOMDocument
     */
    public function getXslt($name) {
        $xslPath = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Xslt' . DIRECTORY_SEPARATOR . ucfirst($name) . '.xsl');
        if (!file_exists($xslPath)) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('XSL file "'.$xslPath.'" does not exist.');
        }
        $xsl = new DOMDocument;
        $xsl->load($xslPath);
        return $xsl;
    }

}