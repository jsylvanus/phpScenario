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
 * Identity provider abstract
 *
 * Provider objects are set via static methods in Scenario_Identity, and are
 * used to define the default method for retrieving an identity object when
 * none is provided to a function (as in the case of an optional parameter).
 *
 * @abstract
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
abstract class Scenario_Identity_Provider {

    /**
     * Returns an Identity object.
     *
     * @param array $params (Optional) parameters to be passed to the provider/identity
     * @return Scenario_Identity provided Scenario_Identity object
     */
    public abstract function getIdentity(array $params);

}