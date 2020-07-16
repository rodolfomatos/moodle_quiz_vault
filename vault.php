#!/usr/bin/php
<?php

// $sudo -u www-data /usr/bin/php admin/cli/backup_quizzes.php --quizid=2 --destination=/moodle/backup/\n

require_once('conf/config.php');
require_once('lib/lib.php');

//----------------------------------------------------------------------

global $VAULT;
$VAULT->NOW = now();

$VAULT->logfile=$VAULT->logs.'/'.dating($VAULT->logfile);
//----------------------------------------------------------------------
// Header
$ARGH=arguments($argv);
if(isset($ARGH['t']) && $ARGH['t']) { 
	$timestamp=$ARGH['t']; 
} else {
//	$timestamp=$VAULT->NOW-300;
	$timestamp=0;
}
//----------------------------------------------------------------------
// Main

$log="";
$div="//----------------------------------------------------------------------";
foreach (quizzes2backup($timestamp) as $q) {
	$last_backup=last_backup_of($q->id);
	$quiz_closetime=mydate(quiz_closetime($q->id));
	$quiz_last_finished_attempt=mydate(quiz_last_finished_attempt($q->id));
	

	//if($last_backup == "" || (($quiz_closetime > $last_backup) && ($quiz_last_finished_attempt > $last_backup))){
	if($last_backup == "" || 
		(($quiz_closetime > $last_backup)
			&& ($quiz_last_finished_attempt > $last_backup)
			&& ($quiz_closetime < now()))){
		
		$log="\n";

		$log_id = $div."\n// Backup (start): ".$q->id."\n";
		$log .= $log_id;
		
		$log .= "\n// Last backup                : ".$last_backup;
		$log .= "\n// Quiz closetime             : ".$quiz_closetime;
		$log .= "\n// Quiz last finished attempt : ".$quiz_last_finished_attempt;
		$log .= "\n\n";
		
		$log .= shell_exec("php lib/backup_quizzes.php --quizid=$q->id --destination=$VAULT->backups 2>&1");
		
		$log .= "\n\n// Backup (end): $q->id\n".$div."\n";
		print $log_id.$div."\n";
		writeToLog($log);
	} 
	//else {

	//	$log .= "\n// Backup file of $q->id was found. Didn't bother to do it again...";
		
	//}
//		writeToLog($log);
}
//----------------------------------------------------------------------
// Process statistics

$log="\n\n";
$log .= $div."// Stats:\n";
$log .= "\nStart processing Time: ".date('d/m/Y-H:i', $VAULT->NOW);
$log .= "\n  End processing Time: ".date('d/m/Y-H:i', now());
$log .= "\nUsed ".getVirtualMemoryTaken()."% of total memory\n";
//------------------------------------------------------------------------
// DEBUG
//print_debug();
//------------------------------------------------------------------------

//----------------------------------------------------------------------
// Footer
//version();

