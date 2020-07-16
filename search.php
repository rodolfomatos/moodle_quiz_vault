#!/usr/bin/php
<?php

define('CLI_SCRIPT', 1);

// Vault configuration
require(dirname(__FILE__).'/conf/config.php');

// Bibliotecas do Moodle
require_once($CFG->libdir.'/dmllib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

// Bibliotecas do Vault
require_once(dirname(__FILE__).'/lib/lib.php');

global $VAULT;

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'quizid' => false,
    'cmid' => false,
    'help' => false,
    ), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !(($options['cmid']) || ($options['quizid'])) || (($options['cmid']) && ($options['quizid']))) {
    $help = <<<EOL
\nPerform conversion between cmid and quizid of Moodle Quizzes.

Options:
--quizid=INTEGER            Quiz ID for backup.
--cmid=INTEGER              Context ID of the module (quiz activity)
-h, --help                  Print out this help.

Example:
\$/usr/bin/php search.php --cmid=1234\n\n
EOL;

    echo $help;
    //print_r($options);
    die;
}

//print_r($options);
if(isset($options['cmid']) && $options['cmid']!=NULL) print "\nQuizid(".$options['cmid']."): ".get_quizid($options['cmid']);
if(isset($options['quizid']) && $options['quizid']!=NULL) print "\ncmid(".$options['quizid']."): ".get_cmid_from_quiz($options['quizid']);

print "\n\n";
