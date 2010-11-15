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
 * Data adapter (abstract)
 *
 * Abstract class containing all methods needed.
 *
 * @abstract
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
abstract class Scenario_Data_Adapter {

    const TABLE_EXPERIMENTS = 'experiments';
    const TABLE_TREATMENTS = 'treatments';
    const TABLE_USERS_TREATMENTS = 'users_treatments';


    /**
     *
     * @var Scenario_Core instance of the core that contains this adapter.
     */
    protected $_core;

    /**
     * Sets the core to use for this adapter.
     *
     * @param Scenario_Core $core Core instance to use for any calls in this adapter.
     */
    public function setCore(Scenario_Core $core) {
        $this->_core = $core;
    }

    /**
     * Gets the core instance in use by this adapter.
     * 
     * @return Scenario_Core|null Core instance to use.
     */
    public function getCore() {
        return $this->_core;
    }

    /**
     * Get an experiment by its name.
     *
     * Fetch a test from the data source by its unique name
     *
     * @param string $name      Unique name of the desired experiment record.
     * @return Scenario_Experiment    The test object associated with the provided unique name. Returns null if not found.
     */
    public abstract function GetExperimentByName($name);

    /**
     * Get extended data for an experiment.
     *
     * Deserialize and return the data stored in association with an experiment.
     *
     * @param string $name      Unique name of the desired experiment record.
     * @return array            Array of data stored in the requested test record.
     */
    public abstract function GetExperimentData($name);

    /**
     * Clears all records for a given treatment/experiment combo.
     *
     * Deletes all records of the desired experiment/treatment. Useful if
     * you want to keep the data for a few treatments (control, for example).
     *
     * @param string|Scenario_Experiment $experiment Experiment that the treatment belongs to.
     * @param string|Scenario_Treatment $treatment Treatment you wish to clear.
     * @param bool Whether or not to delete the actual treatment record after clearing its data.
     */
    public abstract function ClearTreatment($experiment, $treatment, $delete = false);

    /**
     * Clears all data for an experiment.
     *
     * Clears all collected data for an experiment, optionally deleting the actual experiment record.
     *
     * @param string|Scenario_Experiment $experiment The experiment to clear.
     * @param bool $delete Whether or not to delete the record after clearing its data.
     */
    public abstract function ClearExperiment($experiment, $delete = false);

    /**
     * Get all existing experiments.
     *
     * Retrieves an array of stored Scenario_Experiment objects.
     *
     * @return array An array of Scenario_Experiment objects.
     */
    public abstract function GetExperiments();

    /**
     * Add an experiment
     *
     * Create a new experiment record in the data source, with a unique name and optional serialized data
     *
     * @param string $name      Unique name to assign the experiment.
     * @param array $data       Optional data to be serialized in the record.
     * @return Scenario_Experiment    The created experiment object.
     */
    public abstract function AddExperiment($name, $data = array());

    /**
     * Get an experiment.
     * 
     * Get a experiment object from the data source, optionally creating the record if it doesn't exist.
     *
     * @param string $experimentname Name of the experiment. May be case sensitive.
     * @param bool $create Whether or not to create the experiment if it doesn't exist. (Optional)
     * @return Scenario_Experiment The requested experiment object, or null if unavailable & not created.
     */
    public function GetExperiment($experimentname, $create = true) {
        $experiment = $this->GetExperimentByName($experimentname);
        if (null === $experiment && $create) {
            $experiment = $this->AddExperiment($experimentname);
        }
        return $experiment;
    }

    /**
     * Retrieves a specific treatment object.
     *
     * @param Scenario_Experiment $experiment Experiment to restrict the search to.
     * @param string $name Name of the treatment to retrieve.
     * @return Scenario_Treatment|null Treatment retrieved or null on failure.
     */
    public abstract function GetTreatmentByName(Scenario_Experiment $experiment, $name);

    /**
     * Fetches all possible treatments for a given experiment.
     *
     * @param Scenario_Experiment $experiment
     * @return array Returns an array of Scenario_Treatment objects.
     */
    public abstract function GetTreatmentsForExperiment(Scenario_Experiment $experiment);

    /**
     * Fetch all results for a given experiment as a Scenario_ResultSet
     * 
     * @param string|Scenario_Experiment Experiment to fetch results for
     * @return Scenario_ResultSet results
     */
    public abstract function GetResults($experiment);

    /**
     * Retrieve the current treatment in use by a test/id combo. Returns null if not yet in use.
     *
     * @param Scenario_Experiment $test
     * @param Scenario_Identity $id
     * @return Scenario_Treatment
     */
    public abstract function GetTreatmentForIdentity(Scenario_Experiment $test, Scenario_Identity $id);

    /**
     * Set a test/id combo to a specific treatment
     *
     * @param Scenario_Experiment $test
     * @param Scenario_Treatment $value
     * @param Scenario_Identity $id
     */
    public abstract function SetTreatment(Scenario_Experiment $test, Scenario_Treatment $value, Scenario_Identity $id);

    /**
     * Gets a treatment for a given experiment/ident set and
     * optionally creates and saves a treatment if it doesn't already exist.
     *
     * @param Scenario_Experiment $experiment       Test to get a treatment for.
     * @param Scenario_Identity $id     Identity to get a treatment for.
     * @param bool $create              Whether or not to create the treatment if it doesn't exist.
     * @return Scenario_Treatment       Treatment retrieved.
     */
    public function GetTreatment(Scenario_Experiment $experiment, Scenario_Identity $id, $create = true) {
        $treatment = $this->GetTreatmentForIdentity($experiment, $id);
        if (null === $treatment && $create) {
            $treatment = $experiment->getNewTreatment();
            $this->SetTreatment($experiment, $treatment, $id);
        }
        return $treatment;
    }

    /**
     * Finish a treatment
     *
     * Modify a test/id (treatment) combo as being a completed goal. If the
     * treatment does not exist, the call should be ignored, rather than
     * creating a new treatment.
     *
     * @param Scenario_Experiment $treatment
     * @param Scenario_Identity $id
     */
    public abstract function FinishTreatment(Scenario_Treatment $treatment, Scenario_Identity $id);

}
