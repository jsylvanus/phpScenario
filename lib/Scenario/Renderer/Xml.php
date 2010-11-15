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
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Renderer_Xml extends Scenario_Renderer_Abstract {

    const AS_HTML = 'html';
    const AS_XML = 'xml';

    /**
     *
     * @param string $stylesheet
     */
    public function __construct($stylesheet = self::AS_XML) { // see also: html
        $this->setTranslator($stylesheet);
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
     * @param Scenario_ResultSet $results   The resultset to render.
     * @param bool $capture     (optional) Whether to capture the results rather than render them to output.
     * @return string   The rendered result document as a string.
     */
    public function renderSet(Scenario_ResultSet $results, $capture = false) {
        $results = $this->resultsAsXml($results);

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
     * @param Scenario_Result|Scenario_ResultSet $resultSet
     * @return DOMDocument
     */
    public function resultsAsXml($resultSet) {
        $xml = new XMLWriter();
        $xml->openMemory();

        $data = $this->getDataFromResults($resultSet);

        $xml->startElement('experimentresults'); // root
        $xml->writeAttribute('totalresults', $data['totalRecords']);

        foreach($data['results'] as $expid => $results) {

            $xml->startElement('experiment');

            $controlName = 'default'; // maybe make this an option eventually?
            $cTotal = $results[$controlName]['total'];
            $cComplete = $results[$controlName]['completed'];
            $cRate = floatval($cComplete) / floatval($cTotal);

            $xml->writeAttribute('name', $expid);

            $eTotal = $results['_total'];
            $xml->writeAttribute('total', $eTotal);

            foreach ($results as $key => $val) {

                if ($key != '_total') {
                    $tTotal = $val['total'];
                    $tComplete = $val['completed'];
                    $tRate = floatval($tComplete) / floatval($tTotal);
                    
                    $xml->startElement('treatment');
                    $xml->writeAttribute('name', $key);

                    if ($key == $controlName) {
                        $xml->writeAttribute('control', 'control');
                    }

                    $xml->startElement('rawdata');
                    $xml->writeAttribute('total', $tTotal);
                    $xml->writeAttribute('completed', $tComplete);
                    $xml->endElement(); // rawdata

                    $xml->startElement('statistics');

                    if ($key != $controlName) {
                        $zscore = ($tRate - $cRate) / sqrt( ( ($tRate * (1.0 - $tRate)) / floatval($tTotal)) + ( ($cRate * (1.0 - $cRate)) / floatval($cTotal)) );
                        $xml->writeAttribute('zscore', $zscore);
                    }

                    $xml->writeAttribute('conversion', $tRate * 100.0);

                    $xml->endElement(); // statistics

                    $xml->endElement(); // treatment
                }
                
            }
            
            $xml->endElement(); // experiment
        }

        $xml->endElement(); // experimentresults (root)

        $dom = new DOMDocument;
        $dom->loadXML($xml->outputMemory());
        return $dom;
    }

    /**
     * Get an array that summarizes the result set.
     *
     * Compiles resultset data into an array of summarized data, including
     * number of results and number completed for each experiment and treatment.
     *
     * @param Scenario_ResultSet $resultSet
     * @return array
     */
    private function getDataFromResults($resultSet) {

        if ($resultSet instanceof Scenario_Result) {
            require_once 'Scenario/ResultSet.php';
            $resultSet = new Scenario_ResultSet(array($resultSet));
        }

        if (!($resultSet instanceof Scenario_ResultSet)) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('resultsAsXml only accepts Scenario_Result and Scenario_ResultSet objects.');
        }

        $data = array(
            'results' => array(),
            'totalRecords' => 0
        );

        foreach($resultSet as $result) {
            if ($result instanceof Scenario_Result) {
                $expid = $result->getExperiment()->getExperimentID();
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
                $data['totalRecords']++;
                $data['results'][$expid]['_total']++;
            } else {
                require_once 'Scenario/Exception.php';
                throw new Scenario_Exception('Scenario_ResultSet must contain only Scenario_Result objects.');
            }
        }

        return $data;
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