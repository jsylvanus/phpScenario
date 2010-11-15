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
 * Identity generator
 *
 * Static methods for identity object generation.
 * @deprecated Deprecated since version 0.1, use static methods in Scenario_Identity.
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_Identity_Generator {

    /**
     * Retrieves the default auto-generated identity.
     * 
     * The default identity generator is currently sessionIdent()
     *
     * @static
     * @deprecated
     * @todo    Base on default identity setting in core
     * @return Scenario_Identity    Identity for use in getting / storing treatments.
     */
    public static function getIdent() {
        require_once 'Scenario/Identity.php';
        return Scenario_Identity::getIdentity();
    }

    /**
     * Creates a new Scenario_Identity object using session_id as the identifier key.
     *
     * @static
     * @deprecated
     * @return Scenario_Identity
     */
    public static function sessionIdent() {
        require_once 'Scenario/Identity.php';
        require_once 'Scenario/Identity/Provider/Session.php';

        $provider = Scenario_Identity::getProvider();
        if (!($provider instanceof Scenario_Identity_Provider_Session)) {
            $provider = new Scenario_Identity_Provider_Session();
        }

        return $provider->getIdentity();
    }

}
