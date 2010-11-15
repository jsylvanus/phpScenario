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
 * @see Scenario_Data_Adapter
 */
require_once 'Scenario/Data/Adapter.php';

/**
 * Data adapter for Zend Framework db objects.
 *
 * Adapter class used to store and retrieve experiment data from a database,
 * handled via a Zend_Db_Adapter instance. Requires Zend Framework.
 * See http://framework.zend.com/
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Data_Adapter_Zend extends Scenario_Data_Adapter {

    /**
     * Zend db adapter object.
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_dbAdapter;

    /**
     * Table definitions, drawn from core option 'db.tables'
     *
     * @var array
     */
    protected $_tables;

    /**
     * Getter override, used for table lookups.
     * 
     * @param string $name
     * @return string
     */
    public function __get($name) {
        if ($this->_tables == null) {
            $settings = $this->getCore()->getOption('db');
            if (is_array($settings) && array_key_exists('tables', $settings)) {
                $this->_tables = $settings['tables'];
            } else {
                $this->_tables = array(
                    self::TABLE_EXPERIMENTS => self::TABLE_EXPERIMENTS,
                    self::TABLE_TREATMENTS => self::TABLE_TREATMENTS,
                    self::TABLE_USERS_TREATMENTS => self::TABLE_USERS_TREATMENTS
                );
            }
        }
        switch($name) {
            case 'experimentsTable':
                return (is_array($this->_tables) && array_key_exists(self::TABLE_EXPERIMENTS, $this->_tables)) ?
                        $this->_tables[self::TABLE_EXPERIMENTS] : self::TABLE_EXPERIMENTS;
            case 'treatmentsTable':
                return (is_array($this->_tables) && array_key_exists(self::TABLE_TREATMENTS, $this->_tables)) ?
                        $this->_tables[self::TABLE_TREATMENTS] : self::TABLE_TREATMENTS;
            case 'usersTreatmentsTable':
                return (is_array($this->_tables) && array_key_exists(self::TABLE_USERS_TREATMENTS, $this->_tables)) ?
                        $this->_tables[self::TABLE_USERS_TREATMENTS] : self::TABLE_USERS_TREATMENTS;
        }
        return null;
    }

    /**
     * Constructor.
     *
     * Recommended method for setting the Zend adapter.
     *
     * @param Zend_Db_Adapter_Abstract $adapter Zend_Db_Adapter_Abstract to use for data storage.
     */
    public function __construct(Zend_Db_Adapter_Abstract $adapter = null) {
        if ($adapter !== null)
            $this->setAdapter($adapter);
    }

    /**
     * Assigns a new or existing zend db adapter as the db adapter for this instance.
     *
     * @param Zend_Db_Adapter_Abstract $adapter Zend DB Adapter to assign to this data adapter
     */
    public function setAdapter(Zend_Db_Adapter_Abstract $adapter) {
        $this->_dbAdapter = $adapter;
    }

    /**
     * Get the current Zend_Db_Adapter_Abstract object.
     *
     * Gets the DB adapter to use, creating it from core settings if it doesn't already exist.
     * Requires that the ['db']['uri'] key be set to a parsable url, in the format
     * <code>
     * zend_adapter://username:password@hostname/database
     * </code>
     * Where zend_adapter may be any string accepted by the first parameter in Zend_Db::factory(...)
     *
     * @return Zend_Db_Adapter_Abstract Adapter to use for all data storage operations.
     */
    public function getDbAdapter() {
        if ($this->_dbAdapter == null) {
            $settings = $this->getCore()->getOption('db');

            if (!array_key_exists('uri', $settings)) {
                require_once 'Scenario/Data/Exception.php';
                throw new Scenario_Data_Exception('db.uri key not found in core settings.');
            }

            $dbParse = parse_url($settings['uri']);

            require_once 'Zend/Db.php';
            
            $this->_dbAdapter = Zend_Db::factory($dbParse['scheme'], array(
                'host'      => $dbParse['host'],
                'dbname'    => trim($dbParse['path'], "/\\ "),
                'username'  => $dbParse['user'],
                'password'  => $dbParse['pass']
            ));
        }
        return $this->_dbAdapter;
    }

    /**
     * Retrieve an experiment by name.
     *
     * Fetch an experiment from the data source by its unique name. May be case sensitive.
     *
     * @param string $name      Unique name of the desired experiment record.
     * @return Scenario_Experiment    The experiment object associated with the provided unique name. Returns null if not found.
     */
    public function GetExperimentByName($name) {
        $query = $this->getDbAdapter()->select()->from($this->experimentsTable)
                ->where('name = ?',$name);
        $result = $this->getDbAdapter()->fetchRow($query);
        if ($result == null) {
            return null;
        }
        
        /**
         * @see Scenario_Experiment
         */
        require_once 'Scenario/Experiment.php';

        return new Scenario_Experiment($result['name'], $result['id'], false);
    }

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
    public function ClearTreatment($experiment, $treatment, $delete = false) {
        if (is_string($experiment))
            $experiment = $this->GetExperimentByName($experiment);
        if (!($experiment instanceof Scenario_Experiment)) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Experiment is not an instance of Scenario_Experiment.');
        }

        if (is_string($treatment))
            $treatment = $this->GetTreatmentByName($experiment, $treatment);
        if (!($treatment instanceof Scenario_Treatment)) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Treatment is not an instance of Scenario_Treatment.');
        }

        try {
            $this->getDbAdapter()->beginTransaction();

            $this->getDbAdapter()->delete(
                $this->usersTreatmentsTable,
                array(
                    $this->quote('experiment_id = ?', $experiment->getRowID()),
                    $this->quote('treatment_id = ?', $treatment->getRowId())
                )
            );
            
            if ($delete) {
                $this->getDbAdapter()->delete(
                    $this->treatmentsTable,
                    $this->quote('id = ?',$treatment->getRowId())
                );
            }

            $this->getDbAdapter()->commit();
        } catch (Exception $e) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Clears all data for an experiment.
     *
     * Clears all collected data for an experiment, optionally deleting the actual experiment record.
     *
     * @param string|Scenario_Experiment $experiment The experiment to clear.
     * @param bool $delete Whether or not to delete the record after clearing its data.
     */
    public function ClearExperiment($experiment, $delete = false) {
        if (is_string($experiment))
            $experiment = $this->GetExperimentByName($experiment);
        if (!($experiment instanceof Scenario_Experiment)) {
            /**
             * @see Scenario_Data_Exception
             */
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Experiment is not an instance of Scenario_Experiment.');
        }

        $this->getDbAdapter()->beginTransaction();

        $this->getDbAdapter()->delete(
            $this->usersTreatmentsTable,
            $this->quote('experiment_id = ?',$experiment->getRowID())
        );

        $this->getDbAdapter()->delete(
            $this->treatmentsTable,
            $this->quote('experiment_id = ?',$experiment->getRowID())
        );

        if ($delete) {
            $this->getDbAdapter()->delete(
                $this->experimentsTable,
                $this->quote('id = ?',$experiment->getRowID())
            );
        }

        $this->getDbAdapter()->commit();
    }

    /**
     * Get all existing experiments.
     *
     * Retrieves an array of stored Scenario_Experiment objects.
     *
     * @return array An array of Scenario_Experiment objects.
     */
    public function GetExperiments() {

        $this->getDbAdapter()->select()->from($this->experimentsTable);
        $results = $this->getDbAdapter()->fetchAll($query);

        $experiments = array();

        /**
         * @see Scenario_Experiment
         */
        require_once 'Scenario/Experiment.php';

        foreach($results as $key => $val) {
            $experiments[] = new Scenario_Experiment($val['name'], $val['id'], false);
        }

        return $experiments;
    }

    /**
     * Gets the ID of an experiment, if it exists in the database.
     * 
     * @param string $name
     * @return int
     */
    public function GetExperimentID($name) {
        $query = $this->getDbAdapter()->select()->from($this->experimentsTable)
                ->where('name = ?',$name);
        $result = $this->getDbAdapter()->fetchRow($query);
        if ($result == null) {
            return null;
        }
        return intval($result['id']);
    }

    /**
     * Get an existing treatment.
     *
     * Retrieve the current treatment in use by a Experiment/id combo. Returns null if not yet in use.
     *
     * @param Scenario_Experiment $experiment
     * @param Scenario_Identity $id
     * @return Scenario_Treatment|null
     */
    public function GetTreatmentForIdentity(Scenario_Experiment $experiment, Scenario_Identity $id) {
        $experiment_id = $this->GetExperimentID($experiment->getExperimentID());
        if ($experiment_id == null) return null;

        $query = $this->getDbAdapter()->select()
                ->from($this->usersTreatmentsTable)
                ->joinLeft($this->treatmentsTable,
                    $this->treatmentsTable.'.id = '.$this->usersTreatmentsTable.'.treatment_id')
                ->where('identity = ?', $id->getDbIdent())
                ->where($this->treatmentsTable.'.experiment_id = ?', $experiment_id);
        $result = $this->getDbAdapter()->fetchRow($query);

        if ($result === false) {
            return null;
        }
        
        /**
         * @see Scenario_Treatment
         */
        require_once 'Scenario/Treatment.php';

        return new Scenario_Treatment($result['name'], $experiment, $result['id']);
    }

    /**
     * Get a specific treatment by name.
     *
     * Retrieves a specific treatment object, including its primary key (row id).
     *
     * @param Scenario_Experiment $experiment Experiment to restrict the search to.
     * @param string $name Name of the treatment to retrieve.
     * @return Scenario_Treatment|null Treatment retrieved or null on failure.
     */
    public function GetTreatmentByName(Scenario_Experiment $experiment, $name) {
        $query = $this->getDbAdapter()->select()
                ->from($this->treatmentsTable)
                ->where('name = ?', $name)
                ->where('experiment_id = ?', $experiment->getRowID());
        $result = $this->getDbAdapter()->fetchRow($query);
        
        if ($result === false)
            return null;
        
        /**
         * @see Scenario_Treatment
         */
        require_once 'Scenario/Treatment.php';
        
        return new Scenario_Treatment($result['name'], $experiment, $result['id']);
    }

    /**
     * Get available treatments for an experiment.
     *
     * Fetches all possible treatments for a given experiment. They must have already
     * been registered in the database.
     *
     * @param Scenario_Experiment $experiment
     * @return array Returns an array of Scenario_Treatment objects.
     */
    public function GetTreatmentsForExperiment(Scenario_Experiment $experiment) {
        if ($experiment->getRowID() === null)
            $experiment = $this->GetExperimentByName($experiment->getExperimentID());
        if ($experiment->getRowID() === null) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Experiment does not exist in database, unable to get row ID.');
        }
        $query = $this->getDbAdapter()->select()->from($this->treatmentsTable)
                ->where('experiment_id = ?', $experiment->getRowID());
        $results = $this->getDbAdapter()->fetchAll($query);
        if (count($results) > 0) {
            $output = array();
            foreach($results as $val) {
                $output[] = new Scenario_Treatment($val['name'], $experiment, $val['id']);
            }
            return $output;
        }
        return null;
    }

    /**
     * Set a Experiment/id combo to a specific treatment
     *
     * @param Scenario_Experiment $experiment
     * @param Scenario_Treatment $value
     * @param Scenario_Identity $id
     */
    public function SetTreatment(Scenario_Experiment $experiment, Scenario_Treatment $value, Scenario_Identity $id) {
        if ($experiment->getRowID() == null)
            $experiment = $this->GetExperimentByName($experiment->getExperimentID());
        
        if ($experiment === null || !is_int($experiment->getRowID())) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Experiment does not exist.');
        }

        $treatment = $this->GetTreatmentForIdentity($experiment, $id);
        
        if ($treatment !== null) {
            // UPDATE EXISTING
            $treatmentID = $this->GetTreatmentID($experiment, $value->getName());
            if ($treatmentID == null) {
                $this->AddTreatment($experiment, $value->getName());
                $treatmentID = $this->GetTreatmentID($experiment, $value->getName());
            }
            if ($treatmentID !== null) {
                $result = $this->getDbAdapter()->update($this->usersTreatmentsTable,
                    array('treatment_id'=>$treatmentID),
                    $this->quote('identity = ? AND experiment_id = ?', array($id->getDbIdent(), $experiment->getRowID()))
                );
            }
        } else {
            // CREATE NEW
            
            // check to see if the treatment specified actually exists & create it if not
            $check = $this->GetTreatmentByName($experiment, $value->getName());
            if ($check === null) { // treatment specified does not exist, must be created
                if ($this->AddTreatment($experiment, $value->getName())) {
                    $check = $this->GetTreatmentByName($experiment, $value->getName()); // should exist now
                } else {
                    require_once 'Scenario/Data/Exception.php';
                    throw new Scenario_Data_Exception('Scenario was unable to create a new treatment record.');
                }
            }

            // insert the new treatment/identity record
            if ($check !== null) { // sanity

                // Zend_Registry::get('log')->log(print_r($check, true), Zend_Log::DEBUG);

                $result = $this->getDbAdapter()->insert($this->usersTreatmentsTable, array(
                    'identity' => $id->getDbIdent(),
                    'treatment_id' => $check->getRowId(),
                    'experiment_id' => $experiment->getRowID(),
                    'completed' => 0
                ));
            }

        }
    }
    
    /**
     * Add a treatment
     * 
     * @param Scenario_Experiment $exp
     * @param string $treatmentname 
     * @return bool Whether the operation was successful
     */
    protected function AddTreatment(Scenario_Experiment $exp, $treatmentname) {
        if (!is_string($treatmentname)) {
            /**
             * @see Scenario_Data_Exception
             */
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Treatment name must be a string.');
        }

        $result = $this->getDbAdapter()->insert($this->treatmentsTable,array(
            'name' => $this->quote($treatmentname, null),
            'experiment_id' => $exp->getRowID()
        ));

        if ($result > 0) {
            return true;
        }
        return false;
    }

    /**
     * GetTreatmentID
     *
     * @param Scenario_Experiment $exp
     * @param string $treatmentname
     * @return int primary key (int) of the treatment in question
     */
    protected function GetTreatmentID(Scenario_Experiment $exp, $treatmentname) {
        $query = $this->getDbAdapter()->select()->from($this->treatmentsTable)
                ->where('experiment_id = ?', $exp->getRowID())
                ->where('name = ?', $treatmentname);
        $result = $this->getDbAdapter()->fetchRow($query);

        if ($result !== null)
            return $result['id'];

        return null;
    }

    /**
     * Getch all results for a given experiment as a Scenario_ResultSet
     * 
     * @param mixed Experiment to fetch results for, accepts string or Scenario_Experiment object.
     * @return Scenario_ResultSet Result collection.
     */
    public function GetResults($experiment) {
        if (is_string($experiment))
            $experiment = $this->GetExperimentByName($experiment);
        if (!($experiment instanceof Scenario_Experiment)) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Experiment is not an instance of Scenario_Experiment');
        }

        $exps = $this->experimentsTable;
        $trts = $this->treatmentsTable;
        $usrs = $this->usersTreatmentsTable;

        $query = $this->getDbAdapter()->select()
                ->from($usrs)->joinLeft($trts, $usrs . '.treatment_id = ' . $trts . '.id')
                ->where($trts.'.experiment_id = ?', $experiment->getRowID());

        $results = $this->getDbAdapter()->fetchAll($query);

        /**
         * @see Scenario_ResultSet
         */
        require_once 'Scenario/ResultSet.php';

        /**
         * @see Scenario_Result
         */
        require_once 'Scenario/Result.php';

        /**
         * @see Scenario_Identity
         */
        require_once 'Scenario/Identity.php';

        $out = new Scenario_ResultSet();
        foreach($results as $key => $val) {
            $out[] = new Scenario_Result($experiment, $val['name'], new Scenario_Identity($val['identity']), !!$val['completed']);
        }
        return $out;
    }

    /**
     * Modify a treatment/id combo as being a completed goal
     *
     * @param Scenario_Treatment $treatment
     * @param Scenario_Identity $id
     * @return bool Success
     */
    public function FinishTreatment(Scenario_Treatment $treatment, Scenario_Identity $id) {
        if (!$treatment->isValid()) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Treatment provided to FinishTreatment must be valid.');
        }
        // make sure we actually have this treatment stored in the DB already
        $storedTreatment = $this->GetTreatmentByName($treatment->getExperiment(), $treatment->getName());
        if ($storedTreatment === null) {
            require_once 'Scenario/Data/Exception.php';
            throw new Scenario_Data_Exception('Treatment to be finished does not exist.');
        }
        // update the user-treatment record
        $result = $this->getDbAdapter()->update($this->usersTreatmentsTable, array(
               'completed' => 1
            ), array(
                $this->quote('identity = ?', $id->getDbIdent()),
                $this->quote('treatment_id = ?', $storedTreatment->getRowId())
            )
        );
        return $result > 0;
    }

    /**
     *
     * @param string $name
     * @todo
     */
    public function GetExperimentData($name) {

    }

    /**
     * Create a new Experiment record in the data source, with a unique name and optional serialized data
     *
     * @param string $name      Unique name to assign the experiment.
     * @param array $data       Optional data to be serialized in the record.
     * @return Scenario_Experiment    The created experiment object.
     */
    public function AddExperiment($name, $data = array()) {
        $db = $this->getDbAdapter();
        $changes = $db->insert($this->experimentsTable, array(
            'name' => $this->quote($name, null),
            'data' => serialize($data)
        ));
        return $this->GetExperimentByName($name);
    }

    /**
     * Safely quote strings for database insertion.
     *
     * A wrapper for Zend_Db_Adapter's quoteInto method.
     *
     * @param string $string
     * @param array $vars
     * @return string
     */
    private function quote($string, $vars) {
        return $this->getDbAdapter()->quoteInto($string, $vars);
    }



}
