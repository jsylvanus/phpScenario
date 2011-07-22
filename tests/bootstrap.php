<?php

// unit tests setup goes here
// autoloader etc.

// Ensure lib dir is in include_path
set_include_path(implode(PATH_SEPARATOR, array(
                                              realpath(dirname(__FILE__) . '/../lib'),
                                              get_include_path()
                                         )));

// simple class autoloader
function include_path_autoloader($className)
{
    require_once $className . '.php';
}

spl_autoload_register('include_path_autoloader');