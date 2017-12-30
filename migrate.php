#!/usr/bin/env php
<?php

require_once "vendor/autoload.php";

use Trac2GitLab\Migration;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt(array(
	array('t', 'trac', Getopt::REQUIRED_ARGUMENT, 'Trac URL'),
    array('g', 'gitlab', Getopt::REQUIRED_ARGUMENT, 'GitLab URL'),
    array('k', 'token', Getopt::REQUIRED_ARGUMENT, 'GitLab API private token'),
    array('p', 'project', Getopt::REQUIRED_ARGUMENT, 'GitLab project to which the tickets should be migrated'),

    // Both are optional, but at least must be present:
    array('c', 'component', Getopt::OPTIONAL_ARGUMENT, 'Migrate open tickets of a specific Trac component'),
    array('q', 'query', Getopt::OPTIONAL_ARGUMENT, 'Migrate all tickets matching this Trac query (e.g. "id=1234" or "status=!closed&owner=dachaz")'),
    
    // Trully optional
    array('m', 'map', Getopt::OPTIONAL_ARGUMENT, 'Map of trac usernames to git usernames in the following format "tracUserA=gitUserX,tracUserB=gitUserY"'),
    array('a', 'admin', Getopt::NO_ARGUMENT, 'Indicates that the GitLab token is from an admin user and as such tries to migrate the ticket reporter as well. If the reporter is not part of the provided GitLab project, the reporter will be set to the Admin user owning the token.'),
    array('l', 'link', Getopt::NO_ARGUMENT, 'Create a link back to the original Trac ticket in the migrated issue'),
    array('v', 'version', Getopt::NO_ARGUMENT, 'Just shows the version'),

    // added my @mta59066
    array(null, 'labelcomponent', Getopt::NO_ARGUMENT, 'Label component name in GitLab'),
    array(null, 'labelmilestone', Getopt::NO_ARGUMENT, 'Label milestone in GitLab'),
    array(null, 'addlabel', Getopt::REQUIRED_ARGUMENT, 'Add another custom label to all tickets'),
    array(null, 'maxtickets', Getopt::REQUIRED_ARGUMENT, 'Maximum number of tickets to import'),
    array(null, 'showonly', Getopt::NO_ARGUMENT, 'Show what will be done, do not execute')
));

try {
    $getopt->parse();

    if ($getopt->getOption('version')) {
        echo 'Trac to Gitlab v' . getVersion() . "\n";
        exit(0);
    }

    // Validate the parameters
    validateRequired($getopt, array('trac', 'gitlab', 'token', 'project'));
    if (is_null($getopt->getOption('component')) && is_null($getopt->getOption('query'))) {
    	throw new UnexpectedValueException("At least one of 'component' or 'query' must have a value");
    }

    // Convert user mapping from string to array
    $userMapping = array();
    if (!is_null($getopt->getOption('map'))) {
		if(preg_match('/(.+?=.+?,?)+/', $getopt->getOption('map')) == false) {
			throw new UnexpectedValueException("Invalid format for 'map' option");
		}

    	foreach (explode(',', $getopt->getOption('map')) as $mapping) {
    		$mappingArray = explode('=', $mapping);
    		$userMapping[$mappingArray[0]] = $mappingArray[1];
    	}
    }

    // Actually migrate
    $migration = new Migration($getopt->getOption('gitlab'), $getopt->getOption('token'), $getopt->getOption('admin'), $getopt->getOption('trac'), $getopt->getOption('link'), $userMapping, $getopt->getOption('labelcomponent'), $getopt->getOption('labelmilestone'), $getopt->getOption('addlabel'), $getopt->getOption('maxtickets'), $getopt->getOption('showonly'));
	// If we have a component, migrate it
	if (!is_null($getopt->getOption('component'))) {
		$migration->migrateComponent($getopt->getOption('component'), $getopt->getOption('project'), $getopt->getOption('maxtickets'));
	}
	// If we have a custom query, migrate it 
	if (!is_null($getopt->getOption('query'))) {
		$migration->migrateQuery($getopt->getOption('query'), $getopt->getOption('project'), $getopt->getOption('maxtickets'));
	}
} catch (UnexpectedValueException $e) {
    echo "Error: ".$e->getMessage()."\n";
    echo $getopt->getHelpText();
    exit(1);
} catch (\Gitlab\Exception\RuntimeException $e) {
	echo "GitLab Error: ".$e->getMessage()."\n";
	exit(1);
} catch (\JsonRPC\AccessDeniedException $e) {
	echo "Trac Error: ".$e->getMessage()."\n";
	exit(1);
} catch (\JsonRPC\ConnectionFailureException $e) {
	echo "Trac Error: ".$e->getMessage()."\n";
	exit(1);
} catch (\JsonRPC\ServerErrorException $e) {
	echo "Trac Error: ".$e->getMessage()."\n";
	exit(1);
}

function validateRequired($getopt, $requiredParams) {
	foreach($requiredParams as $param) {
		if (is_null($getopt->getOption($param))) {
			throw new UnexpectedValueException("Option '$param' must have a value");
		}
	}
}

function getVersion() {
    $str = file_get_contents('composer.json');
    $json = json_decode($str, true);
    return $json['version'];
}
