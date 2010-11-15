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
 * @see Scenario_Identity_Provider
 */
require_once 'Scenario/Identity/Provider.php';

/**
 * Identity provider based on session_id.
 *
 * This class serves as the library's default identity provider.
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Identity_Provider_Session extends Scenario_Identity_Provider {
 
    /**
     * Returns an Identity object based on session_id()
     * 
     * getIdentity() returns a base Scenario_Identity object with the identity 
     * string provided by session_id(). If the session is not yet started, 
     * it will throw an error, as when to start the session should be up to 
     * the developer.
     * 
     * @param array $params
     * @return Scenario_Identity the session_id() based Scenario_Identity object
     */
    public function getIdentity(array $params) {
        $id = session_id();
        if ($id == "") {
            /**
             * @see Scenario_Exception
             */
            require_once 'Scenario/Exception.php';

            throw new Scenario_Exception('Unable to use session_id() as an identity string, are you missing a session_start()?');
        }

        /**
         * @see Scenario_Identity
         */
        require_once 'Scenario/Identity.php';

        return new Scenario_Identity(session_id());
    }

}
