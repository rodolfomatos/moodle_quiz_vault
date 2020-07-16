<?php

// Vault configuration
require_once(dirname(__FILE__).'/../conf/config.php');

// Bibliotecas do Moodle
require_once($CFG->libdir.'/dmllib.php');
require_once($CFG->libdir.'/moodlelib.php');

global $VAULT;

//----------------------------------------------------------------------
// To DEBUG or not to DEBUG...

if($VAULT->DEBUG){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT);
//	error_reporting(E_ALL ^ E_STRICT);

	// Valores para DEBUG no Moodle:
	// 32767 Programador
	// 30719 Todas
	//    15 normal
	//     5 minimo
	//     0 nada
	$CFG->debug = 32767;
	$CFG->debugdisplay = true;
	$CFG->debugsmtp=true;
	$CFG->perfdebug=true;
	$CFG->debugstringids=true;
	$CFG->debugvalidators=true;
	$CFG->debugpageinfo=true;
}
//----------------------------------------------------------------------
// Auxiliary functions (start)
//----------------------------------------------------------------------
// 1
function now() {
	global $VAULT;
	
	try {		
//		$debugue[]="1. time_before_setting_timezone: ".time();
//		date_default_timezone_set($VAULT->timezone);
		$t=time();
		$debugue[]="1. time: ".$t;
//		$debugue[]="2. time_after_setting_timezone: ".$t;
		if($VAULT->DEBUG) $VAULT->ERRORS[] = array('now', get_caller_method(), $debugue);
		return $t;
    } catch (Exception $err) {
		$VAULT->ERRORS[] = array('now', get_caller_method(),$debugue, $err->getMessage());
    }
}

//----------------------------------------------------------------------
// 2
function print_debug() {
	global $VAULT;
	if (isset($VAULT->ERRORS) || $VAULT->DEBUG){
		$index=1;
		foreach ($VAULT->ERRORS as $e){
			if(isset($e[2])||($VAULT->DEBUG)) {
				if($index==1) {
					print "\n\nDEBUG\n";		
					var_dump(error_get_last());
				}
				print "[".$index++."]\nfunction: ".$e[0]."\n";
				if (is_array($e[1]) || is_object($e[1])) {
					print "workflow:\n";
					foreach($e[1] as $w){
						print '* '.$w."\n";
					}
				}
				print "\n";
				if(isset($e[2])) print "\nERROR: ".serialize($e[2])."\n";
				print "\n\n";
			}
		}
	}
}

//----------------------------------------------------------------------
// 3
// Ref: http://php.net/manual/en/function.debug-backtrace.php
function get_caller_method()
{
	global $VAULT;
	if($VAULT->get_caller) {
		$traces = debug_backtrace();

		if (isset($traces[2]))
		{
			return $traces[2]['function'];
		}
	}
    return null;
}
//----------------------------------------------------------------------
// 4
function writeToLog($event) {
	global $VAULT, $_SESSION;
    try {
		if(!file_exists($VAULT->logfile)) {
			$log="#<?php die('Forbidden.'); ?>\n";
		} else {
			$log="";
		}
		
		$debugue[]='1.';
		$time=date('d/m/Y:H:i:s', now());
		if(CLI_SCRIPT == 1) {
			$log .= "[".$time."] ".$event."\n";
		} else {
			$log .= "[".$time."] [".$_SESSION['userid']."] [".session_id()."] ".$event."\n";
		}
		file_put_contents($VAULT->logfile, $log, FILE_APPEND | LOCK_EX);
		$debugue[]='2.';
 		$VAULT->ERRORS[] = array('writeToLog', get_caller_method(),$debugue);
 		return NULL;
    } catch (Exception $err) {
 		$VAULT->ERRORS[] = array('writeToLog', get_caller_method(),$debugue,$err->getMessage());
    }
}

//----------------------------------------------------------------------
// 5
// Ref: http://php.net/manual/en/function.memory-get-usage.php
//<source>
//    $a = ' ';
//    do {  $a .= $a . $a; }
//    while (getVirtualMemoryTaken() < 20 );
//</source>

function getVirtualMemoryTaken() {
	// Returns ratio of the process's resident set size  to the
    // physical memory on the machine, expressed as a
    // percentage.
	$pid = getmypid();
	$a = `ps -p $pid v | awk 'END{print $9}'`;
	return $a*1;
}
//----------------------------------------------------------------------
function version(){
	include_once('version.php');
	global $VAULT;
	echo "Version: ".$VAULT->version;
}

//----------------------------------------------------------------------
function help() {
	echo basename(__FILE__, '.php') ;
	print "\n [-h] this help\n";
	print "\n".' Usage example: ./'; echo basename(__FILE__, '.php'); print " -h\n\n";
	 
}
//----------------------------------------------------------------------
function arguments($argv) {
	// Ref: http://php.net/manual/en/features.commandline.php
	// 		(corrected the deprecated ereg calls)
	// $ php myscript.php --user=nobody --password=secret -p --access="host=127.0.0.1 port=456"
	// Array
	// (
	//     [user] => nobody
	//     [password] => secret
	//     [p] => true
	//     [access] => host=127.0.0.1 port=456
	// )
	//
	global $VAULT;
    try {
		$_ARG = array();
		$debugue[]='1. $_ARG='.serialize($_ARG);
		foreach ($argv as $arg) {
		  if (preg_match('/--([^=]+)=(.*)/',$arg,$reg)) {
			$_ARG[$reg[1]] = $reg[2];
		  } elseif(preg_match('/-([a-zA-Z0-9])/',$arg,$reg)) {
				$_ARG[$reg[1]] = 'true';
			}
		}
		$debugue[]='2. $_ARG='.serialize($_ARG);
 		if($VAULT->DEBUG) $VAULT->ERRORS[] = array('arguments',$debugue);
		return $_ARG;
    } catch (Exception $err) {
 		$VAULT->ERRORS[] = array('arguments',$debugue,$err->getMessage());
    }
}

//----------------------------------------------------------------------
// Auxiliary functions (end)
//----------------------------------------------------------------------

//----------------------------------------------------------------------
function subnet_from_quiz( $quiz ) {
        global $CFG, $DB, $DEBUG;
        $sql="SELECT q.subnet FROM {quiz} q WHERE q.id=".$quiz;
        return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function course_id_from_quiz( $quizid ){
        global $CFG, $DB, $DEBUG;
        $courseid = $DB->get_field_sql('SELECT c.id FROM {quiz} q, {course} c WHERE q.course=c.id and q.id='.$quizid);
        return $courseid;
}
//----------------------------------------------------------------------
function course_shortname( $quizid ){
        global $CFG, $DB, $DEBUG;
        $courseshortname = $DB->get_field_sql('SELECT c.shortname FROM {quiz} q, {course} c WHERE q.course=c.id and q.id='.$quizid);
        return $courseshortname;
}
//----------------------------------------------------------------------
function course_fullname( $quizid ){
        global $CFG, $DB, $DEBUG;
        $coursefullname = $DB->get_field_sql('SELECT c.fullname FROM {quiz} q, {course} c WHERE q.course=c.id and q.id='.$quizid);
        return $coursefullname;
}
//----------------------------------------------------------------------
function quiz_is_well_behaved( $quiz ) {
        // timeopen e timeclose > 0 e subnet != ""
        if( (quiz_opentime($quiz)==0) || (quiz_closetime($quiz)==0) || (subnet_from_quiz( $quiz ) == "") || (quiz_max_attempts( $quiz ) > 1) ) {
                return 0;
        } else {
                return 1;
        }
}
//----------------------------------------------------------------------
function quiz_opentime( $quiz ) {
        global $CFG, $DB;
        $sql='SELECT timeopen FROM {quiz} WHERE id='.$quiz;
        return $DB->get_field_sql($sql);        
}
//----------------------------------------------------------------------
function quiz_closetime( $quiz ) {
        global $CFG, $DB;
        $sql='SELECT timeclose FROM {quiz} WHERE id='.$quiz;
        return $DB->get_field_sql($sql);        
}
//----------------------------------------------------------------------
function quiz_max_attempts( $quiz ) {
        global $CFG, $DB;
        $sql="SELECT attempts from {quiz} WHERE id=".$quiz;
        return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function quiz_last_finished_attempt( $quiz ) {
        global $CFG, $DB;
        $sql="SELECT max(timefinish) FROM {quiz_attempts} where quiz=".$quiz;
        return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function summative_quiz_opentime( $quiz ) {
        global $CFG, $DB;
        $sql='SELECT timeopen FROM {quizaccess_summativequiz} WHERE quizid='.$quiz;
        return $DB->get_field_sql($sql);        
}
//----------------------------------------------------------------------
function summative_quiz_closetime( $quiz ) {
        global $CFG, $DB;
        $sql='SELECT timeclose FROM {quizaccess_summativequiz} WHERE quizid='.$quiz;
        return $DB->get_field_sql($sql);        
}
//----------------------------------------------------------------------
function quiz_is_sumative( $quiz ) {
        global $CFG, $DB;
        $sql="SELECT summative from {quizaccess_summativequiz} WHERE quizid=".$quiz;
        return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function quiz_name( $quiz ) {
        global $CFG, $DB;
        $sql="SELECT name from {quiz} WHERE id=".$quiz;
        return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function quizzes2backup( $timestamp="0" ) {
        global $CFG, $DB;
        if(quizaccess_summativequiz_table_exists()){
			$sql='SELECT quizid as id FROM {quizaccess_summativequiz} WHERE timeclose > '.$timestamp;
		} else {
			$sql='SELECT id FROM {quiz} WHERE timeclose > '.$timestamp;
		}
        return $DB->get_records_sql($sql);        
}
//----------------------------------------------------------------------
function quizaccess_summativequiz_table_exists(){
	global $CFG, $DB, $VAULT;
	try{
        $sql='SELECT 1 FROM {quizaccess_summativequiz} LIMIT 1';
		$debugue[]='1. $sql='.$sql;
        $val=$DB->get_field_sql($sql);
        if($val !== FALSE)
			$return=1;
        else
			$return=0;
 		if($VAULT->DEBUG) $VAULT->ERRORS[] = array('quizaccess_summativequiz_table_exists',$debugue);
		return $return;
    } catch (Exception $err) {
 		$VAULT->ERRORS[] = array('quizaccess_summativequiz_table_exists',$debugue,$err->getMessage());
    }
}
//----------------------------------------------------------------------
function quizzes_closed_after( $timestamp=0) {
	foreach (quizzes2backup($timestamp) as $q) {
		print $q->id."\n";
	}
}
//----------------------------------------------------------------------
function quizzes_of_the_day($quizzes, $day, $month, $year, $uo){

    $quizzesarray= array();

    foreach ($quizzes as $quiz) {
        // os testes podem nao ter definido o tempo de inicio e de fim e mesmo assim terem tentativas
        if($quiz->timeopen == 0){
                $inicio=$quiz->attemptsstart;
        } else {
                $inicio=$quiz->timeopen;
        }
        if($quiz->timeclose == 0){
                $fim=$quiz->timeclose;
        } else {
                $fim=$quiz->timeclose;
        }
        // criar o calendario
        $d=gmdate('j',$inicio);
        $d=$d+0;
        $m=gmdate('m',$inicio);
        $m=$m+0;
        $Y=gmdate('Y',$inicio);
        $Y=$Y+0;

        $calendario[$Y][$m][$d][]=$quiz->id;

    if (($day."/".$month."/".$year == $d."/".$m."/".$Y) && (preg_match("/^".strtoupper($uo)."/",course_shortname($quiz->id) ))){

        array_push($quizzesarray, $quiz->id);
    }
  }
  return json_encode($quizzesarray);
}
//----------------------------------------------------------------------
function backups_of( $quizid ) {
	global $VAULT;
	$files=scandir($VAULT->backups);
	$return=array();
	foreach ($files as $f) {
		if(strpos($f,"backup_quiz_".$quizid."_") !== false)
			$return[]=$f;
	}
	return $return;
}
//----------------------------------------------------------------------
function last_backup_of( $quizid ) {
		$backups=backups_of($quizid);
		$dates=array();
		foreach ($backups as $f) {
			$f=str_replace("backup_quiz_".$quizid."_", "", $f);
			$f=str_replace(".mbz", "", $f);
			$f=str_replace("-", "", $f);
			$dates[]=$f;
		}
		sort($dates, SORT_NUMERIC);
		return end($dates);
}
//----------------------------------------------------------------------
function mydate($timestamp){
	return date("YmdHi",$timestamp);
}
//----------------------------------------------------------------------
function dating($filename){
		$f=basename($filename,'.php');
		return $f.'_'.mydate(now()).'.php';
}
function quiz_module_id(){
	global $CFG, $DB;
	$sql='SELECT id FROM {modules} WHERE name="quiz"';	
	return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function get_quizid($cmid) {
	global $CFG, $DB;
	$sql='select instance from {course_modules} where id="'.$cmid.'" and module="16"';
	//$sql='select id, course,instance from {course_modules} where id='.$cmid.' and module=16';
	//$sql='SELECT instance FROM {course_modules} WHERE id='.$cmid.' AND module="'.quiz_module_id().'"';
	return $DB->get_field_sql($sql);
}
//----------------------------------------------------------------------
function get_cmid_from_quiz($quizid){
	global $CFG, $DB;
	$course=course_id_from_quiz($quizid);
	$quiz_module_id=quiz_module_id();
	
	$sql='SELECT id FROM {course_modules} WHERE course='.$course.' AND 
			module='.$quiz_module_id.' AND instance='.$quizid;
	
	return $DB->get_field_sql($sql);
	
//	$cm=get_coursemodule_from_instance('quiz',$quizid);
//	return $cm->id;
}
//----------------------------------------------------------------------
?>
