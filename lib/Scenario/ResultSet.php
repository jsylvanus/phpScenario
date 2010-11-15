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
 * Result collection object
 *
 * An ArrayObject with slightly extended functionality (render function)
 *
 * @category   Scenario
 * @package    Scenario
 * @copyright  Copyright (c) 2010 TK Studios. (http://www.tkstudios.com)
 * @license    http://www.phpscenario.org/license.php     New BSD License
 */
class Scenario_ResultSet extends ArrayObject {

    /**
     * Render the set.
     *
     * Passes itself to the renderSet method of a supplied renderer object.
     *
     * @param Scenario_Renderer_Abstract $renderer Renderer to use.
     */
    public function render(Scenario_Renderer_Abstract $renderer) {
        $renderer->renderSet($this);
    }

}
