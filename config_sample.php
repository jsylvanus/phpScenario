<?php
// Sample PDO adapter setup

// make sure we've got the lib folder in our path (set to the directory where your Scenario folder is)
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/lib'));

// load up Scenario (and therefore scenario_core)
require_once 'Scenario.php';

// load up the adapter we want to use
require_once 'Scenario/Data/Adapter/Pdo.php';

// set up the adapter
$dsn = 'mysql:host=localhost;dbname=scenario';
$adapter = new Scenario_Data_Adapter_Pdo($dsn, 'username', 'password');

// configure the core
Scenario::getInstance()->config(array('adapter'=>$adapter));
