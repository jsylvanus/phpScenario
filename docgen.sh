#!/bin/bash
phpdoc -d ./lib -t ./docs -ti 'phpScenario Documentation' -o HTML:default:default -s on -p on
