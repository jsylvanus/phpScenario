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
 * Identity object
 *
 * Object to be passed to functions requiring an identity for the current case.
 * Subclasses may return different values for database-safe identities.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2011-2012 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Identity {

    /**
     * Default identity provider used when no identity object is provided.
     * 
     * @static
     * @var Scenario_Identity_Provider
     */
    private static $_idProvider;

    /**
     * Sets the default identity provider to be used when no identity is provided.
     *
     * @static
     * @param Scenario_Identity_Provider $provider  New provider to set.
     */
    public static function setProvider(Scenario_Identity_Provider $provider = null) {
        if ($provider !== null) {
            self::$_idProvider = $provider;
        } else {
            require_once 'Scenario/Identity/Provider/Session.php';
            self::$_idProvider = new Scenario_Identity_Provider_Session();
        }
    }

    /**
     * Returns the currently set default identity provider.
     *
     * @static
     * @return Scenario_Identity_Provider Currently set default identity provider.
     */
    public static function getProvider() {
        return self::$_idProvider;
    }

    /**
     * Returns an identity object via the current default provider.
     *
     * @static
     * @param array $params (Optional) Parameters to pass to the identity provider.
     * @return Scenario_Identity Identity generated by the current static identity provider.
     */
    public static function getIdentity($params = array()) {
        if (self::$_idProvider === null) {
            require_once 'Scenario/Exception.php';
            throw new Scenario_Exception('Identity provider is not set.');
        }
        return self::$_idProvider->getIdentity($params);
    }

    /**
     * Identifying string
     *
     * @var string
     */
    protected $_identStr;

    public function __construct($ident) {
        $this->setIdent($ident);
    }

    /**
     * Get database-safe version of identity string.
     *
     * Fetch an identity string that can be used in data storage as a unique identifier.
     * Inheriting Scenario_Identity would allow you to, for example, use session_(ID) or
     * user_(id) depending on the situation.
     *
     * @return string
     */
    public function getDbIdent() {
        return strval($this->_identStr);
    }

    /**
     * Set the database identifier.
     *
     * Alter the unique data storage identifier. Useful if, for example, a user logs in or out.
     * In the base Scenario_Identity class, this is equivalent to setIdent($str)
     *
     * @param string $str New identifier string.
     */
    public function setDbIdent($str) {
        $this->_identStr = $str;
    }

    /**
     * Retrieves the normal identifier object.
     * 
     * Returns an identifying variable or object. Not necessarily the same as the DB Ident.
     * In the case of the base Scenario_Identity object, this should be a string.
     *
     * @return mixed
     */
    public function getIdent() {
        return $this->_identStr;
    }

    /**
     * Sets the normal identifier object.
     *
     * Set the identifying var/object. Not necessarily the same as the DB Ident.
     * In the case of the base Scenario_Identity object, this should be a string.
     *
     * @param mixed $id
     */
    public function setIdent($id) {
        $this->_identStr = $id;
    }

}