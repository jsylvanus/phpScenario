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
 * Core class
 *
 * Base Scenario class, singleton pattern for easy access to the library's functionality.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Core {

    /**
     * Holds the default configuration data, to be selectively overwritten by config method.
     *
     * @static
     * @var array 
     */
    protected static $_defaultConfig = array(
        'classPrefix' => 'Experiment_',
        // the following are not yet in use, but are assumed to be these values.
        'createMissingExperiments' => true,
        'createMissingTreatments' => true,
        'defaultIdentityGenerator' => 'session',
        'defaultExperimentClass' => 'Scenario_Experiment'
    );

    /**
     * Holds the singleton instance.
     *
     * @static
     * @var Scenario_Core
     */
    protected static $_instance;

    /**
     * Accesses the Core singleton instance
     *
     * @static
     * @return Scenario_Core The core instance.
     */
    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Static method for getting a treatment for a given experiment.
     *
     * Short form usage example:
     * <code>
     * // get a treatment by string, using default ID provider (session),
     * // and immediately get the treatment name.
     * $treatmentName = Scenario_Core::Treatment('myTest')->getName();
     * </code>
     * Verbose usage example:
     * <code>
     * $experiment = Scenario_Core::getInstance()->getExperiment('myTest');
     * $ident = Scenario_Identity_Generator::sessionIdent();
     * $treatment = Scenario_Core::Treatment($experiment,$ident);
     * $name = $treatment->getName();
     * </code>
     *
     * @static
     * @param string|Scenario_Experiment $experiment The experiment (or name of the experiment) to get a treatment from.
     * @param Scenario_Identity $id Identity object to get treatment for. Defaults to Scenario_Identity_Generator::getIdent()
     * @param bool $create (Optional) Whether or not to create the treatment if it does not exist. Defaults to true.
     * @return Scenario_Treatment Treatment object acquired.
     */
    public static function Treatment($experiment, Scenario_Identity $id = null, $create = true) {
        // null check
        if ($experiment === null) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Experiment parameter must be a string or instance of Scenario_Experiment.');
        }

        // get the actual experiment for the string value
        if (is_string($experiment))
            $experiment = self::Experiment($experiment);

        // They didn't provide an identity, so grab one from the default (session).
        if ($id == null) {
            $id = self::Identity();
        } else if (!($id instanceof Scenario_Identity)) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Identity provided must be an instance of Scenario_Identity.');
        }

        if (!($experiment instanceof Scenario_Experiment)) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Experiment must be an instance of Scenario_Experiment.');
        }

        return $experiment->getTreatment($id, $create);
    }

    /**
     * Gets the experiment for the specified name.
     *
     * Example:
     * <code>
     * $experiment = Scenario_Core::Experiment('experimentname');
     * </code>
     *
     * @static
     * @param string $name Name of the experiment to retrieve.
     * @return Scenario_Experiment Experiment object for provided name.
     */
    public static function Experiment($name) {
        return self::getInstance()->getExperiment($name);
    }

    /**
     * Calls and returns default Scenario_Identity object from generator.
     *
     * @static
     * @param array $params Parameters to pass to the identity provider.
     * @return Scenario_Identity Default identity object.
     */
    public static function Identity($params = array()) {
        require_once 'Scenario/Identity.php';
        return Scenario_Identity::getIdentity($params);
    }

    /**
     * Shortcut for one-line A/B testing.
     *
     * Example:
     * <code>
     * if (Scenario_Core::IsControl('experimentname')) {
     *      // control version
     * } else {
     *      // alternate version
     * }
     * </code>
     *
     * @static
     * @param string|Scenario_Experiment $experiment Name of the experiment or experiment object.
     * @param null|Scenario_Identity $ident Scenario_Identity object, defaults to Identity method if null.
     * @return bool Whether or not the specified treatment is named "default"
     */
    public static function IsControl($experiment, $ident = null) {
        return self::Treatment($experiment, $ident)->getName() == 'default';
    }

    /**
     * Render the results of an experiment.
     *
     * Example:
     * <code>
     * // to get results as XML
     * $xml = Scenario_Core::RenderXml('myexperiment','xml',true);
     *
     * // to output as HTML
     * Scenario_Core::RenderXml('myexperiment');
     * </code>
     *
     * @static
     * @param string|Scenario_Experiment $experiment The experiment to retrieve results from (string or object)
     * @param string $style (Optional) The style parameter to pass to the Scenario_Renderer_Xml object.
     * @param bool $capture (Optional) Whether or not to capture output rather than echo it.
     */
    public static function RenderXml($experiment, $style = 'html', $capture = false) {
        if (is_string($experiment))
            $experiment = self::Experiment($experiment);

        if ($experiment instanceof Scenario_Experiment) {
            $results = $experiment->getResults();

            require_once 'Scenario/Renderer/Xml.php';
            $renderer = new Scenario_Renderer_Xml($style);

            return $renderer->renderSet($results, $capture);
        }

        require_once 'Scenario/Exception.php';
        throw new Scenario_Exception('Experiment is not an instance of Scenario_Experiment or a string representing one.');
    }


    /**
     * Marks a treatment as being completed.
     *
     * @static
     * @param string|Scenario_Experiment $experiment Experiment to complete
     * @param null|Scenario_Identity $identity Identity to complete for given experiment
     */
    public static function Complete($experiment, $identity = null) {
        if ($identity == null)
            $identity = self::Identity();
        $treatment = self::Treatment($experiment, $identity, false);
        if ($treatment !== null)
            $treatment->finish($identity);
    }
    
    //-----nonstatic members below-----

    /**
     * Configuration data.
     *
     * @var array
     */
    protected $_config;

    /**
     * Data adapter to handle storage & retrieval of testing data.
     *
     * @var Scenario_Data_Adapter
     */
    protected $_adapter;

    /**
     * Constructor. This is usually handled by the singleton pattern, constructing a core
     * outside of that pattern will cause errors on many calls.
     * 
     * @param array $config Configuration options. 
     */
    public function __construct(array $config = array()) {
        $this->config(array_merge(self::$_defaultConfig, $config));
        
        /**
         * @see Scenario_Identity
         */
        require_once 'Scenario/Identity.php';

        /**
         * @see Scenario_Identity_Provider_Session
         */
        require_once 'Scenario/Identity/Provider/Session.php';

        Scenario_Identity::setProvider(new Scenario_Identity_Provider_Session());
    }

    /**
     * Set configuration options.
     *
     * Merges the given options array into the configuration data.
     *
     * Usage example for Zend adapter:
     * <code>
     * Scenario_Core::getInstance()->config( array(
     *      "adapter"   => new Scenario_Data_Adapter_Zend(),
     *      "db"        => array(
     *          "uri"   => "mysqli://user:pass@hostname/schema",
     *          "tables" => array(
     *              "experiments" => "scenario_experiments",
     *              "treatments" => "scenario_treatments",
     *              "users_treatments" => "scenario_users_treatments"
     *          )
     *      )
     * ) );
     * </code>
     *
     * Usage example for Pdo adapter:
     * <code>
     * // define the PDO DSN
     * $dsn = 'mysql:host=localhost;dbname=scenario';
     *
     * // create the adapter
     * require_once 'Scenario/Data/Adapter/Pdo.php';
     * $adapter = new Scenario_Data_Adapter_Pdo($dsn, 'username', 'pass');
     *
     * // configure the core
     * Scenario_Core::getInstance()->config( array( "adapter" => $adapter ) );
     * // Note: if [db][tables] is omitted, adapter will use default table names.
     * </code>
     *
     * @param array $options
     * @return Scenario_Core
     */
    public function config(array $options = array()) {
        if (is_array($this->_config)) {
            $this->_config = array_merge($this->_config, $options);
        } else {
            $this->_config = $options;
        }
        if (array_key_exists('adapter', $options)) {
            $adapter = $options['adapter'];
            if ($adapter instanceof Scenario_Data_Adapter) {
                $this->setAdapter($adapter);
            }
        }
        if (array_key_exists('identityProvider', $options)) {
            if ($options['identityProvider'] instanceof Scenario_Identity_Provider) {
                /**
                 * @see Scenario_Identity
                 */
                require_once 'Scenario/Identity.php';
                Scenario_Identity::setProvider($options['identityProvider']);
            }
        }
        return $this;
    }

    /**
     * Set the data adapter.
     *
     * Sets the data adapter in use by this instance and links the adapter back to this instance.
     *
     * @param Scenario_Data_Adapter $adapter
     * @return Scenario_Core
     */
    public function setAdapter(Scenario_Data_Adapter $adapter) {
        $this->_adapter = $adapter;
        $this->_adapter->setCore($this);
        return $this;
    }

    /**
     * Get the data adapter.
     *
     * @return Scenario_Data_Adapter
     */
    public function getAdapter() {
        return $this->_adapter;
    }

    /**
     * Retrieves a treatment from an experiment,
     *
     * @param mixed $experiment
     * @param Scenario_Identity $id
     * @return Scenario_Treatment
     */
    public function getTreatment($experiment, Scenario_Identity $id) {
        if ($id == null) {
            $id = self::Identity();
        }
        if (is_string($experiment)) {
            $experiment = $this->getExperiment($experiment);
        }
        if ($experiment instanceof Scenario_Experiment) {
            return $experiment->getTreatment($id);
        } else {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Experiment must be a string or an instance of Scenario_Experiment.');
        }
    }

    /**
     * Retrieve an experiment by name.
     * 
     * Gets an experiement by the specified name, first checking for the existence
     * of a class in the format [classPrefix].ucfirst(name), and defaulting to 
     * Scenario_Experiment if unable to find a custom class. Custom classes may 
     * be used to alter the behavior of experiments, particularly in regards to 
     * treatment selection.
     *
     * @param string $experimentname
     * @return Scenario_Experiment
     */
    public function getExperiment($experimentname) {
        $prefix = $this->getOption('classPrefix');

        $className = $experimentname;
        
        if ($prefix != null) $className = $prefix . ucfirst($className);

        if (class_exists($className) && is_subclass_of($className, 'Scenario_Experiment')) {

            // instantiate the custom class
            return new $className($experimentname);

        } else if ($this->getAdapter() !== null) {

            // failing custom creation, we'd much prefer to get it from the database if we can...
            $exp = $this->getAdapter()->GetExperimentByName($experimentname);
            if ($exp != null) return $exp;

        }
        
        // default to an experiment with no row ID.
        require_once 'Scenario/Experiment.php';
        return new Scenario_Experiment($experimentname);
    }

    /**
     * Retrieve the entire configuration array.
     *
     * @return array
     */
    public function getOptions() {
        return $this->_config;
    }

    /**
     * Retrieve a specific value from the configuration array.
     *
     * Retrieves a specific value, sub-arrays delimited by periods.
     * _config['test']['othertest'] may be referenced by
     * <code>
     * Scenario_Core::getInstance()->getOption('test.othertest');
     * </code>
     *
     * @param string $str Period-separated'd path to desired option.
     * @return mixed Value at the given location (or null if it doesn't exist)
     */
    public function getOption($str) {
        $optStr = explode('.', $str);
        if (array_key_exists($optStr[0], $this->_config)) {
            $out = $this->_config[$optStr[0]];
            for ($i = 1, $c = count($optStr); $i < $c; $i++) {
                if (!array_key_exists($optStr[$i], $out))
                    return null;
                $out = $out[$optStr[$i]];
            }
            return $out;
        }
        return null;
    }

}
