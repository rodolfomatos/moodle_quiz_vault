<?php  // Vault configuration file

// Moodle configuration
if(!defined('CLI_SCRIPT'))
	define('CLI_SCRIPT', 1);
require_once('/var/www/html/moodle/config.php');

unset($VAULT);
global $VAULT, $_SERVER;
$VAULT = new stdClass();

$VAULT->only_check_summative_quizzes    = '1';

$VAULT->timezone    = 'GMT';

$VAULT->root		= '/opt/vault';
$VAULT->backups		= $VAULT->root.'/backups';
$VAULT->tmp			= $VAULT->root.'/tmp';
$VAULT->logs		= $VAULT->root.'/logs';

// intervalo de tempo que é verificado: "now()-$VAULT->delta"
$VAULT->delta = 86400;

// Logfile: MUST have the extension .php if it is to be stored under www docroot!
$VAULT->logfile 	= $VAULT->logs.'/vault_log.php';
$VAULT->filelock 	= $VAULT->tmp.'/vault.lck';

// Debug system (start)
$VAULT->DEBUG = false;
$VAULT->get_caller = false;
// Debug system (end)

require_once(dirname(__FILE__) . '/../lib/lib.php');


// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
