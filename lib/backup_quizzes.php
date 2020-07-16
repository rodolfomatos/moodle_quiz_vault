<?php
// This file was changed by Susana Leitão & Rodolfo Matos
//
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script allows to do backup.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2013 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(dirname(__FILE__).'/../conf/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'quizid' => false,
    'cmid' => false,
    'courseshortname' => '',
    'destination' => '',
    'help' => false,
    ), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !(($options['cmid']) || ($options['quizid']))) {
    $help = <<<EOL
Perform backup of the given course activity.

Options:
--quizid=INTEGER            Quiz ID for backup.
--cmid=INTEGER              Context ID of the module (activity)
--destination=STRING        Path where to store backup file. If not set the backup
                            will be stored within the course backup file area.
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/cli/backup_activity.php --cmid=1234 --destination=/moodle/backup/\n
EOL;

    echo $help;
    print_r($options);
    die;
}

$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found");
    die;
}

// Do we need to store backup somewhere else?
$dir = rtrim($options['destination'], '/');
if (!empty($dir)) {
    if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
        mtrace("Destination directory does not exists or not writable.");
        die;
    }
}

// Check that the activity exists.
if ($options['cmid']) {
    $cm = $DB->get_record('course_modules', array('id' => $options['cmid']), '*', MUST_EXIST);
}
// Check that the course exists.
if ($options['quizid']) {
    $cm = get_coursemodule_from_instance('quiz', $options['quizid']);
}
cli_heading('Performing backup...');
$bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
                            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);
// Set the default filename.
$format = $bc->get_format();
$type = $bc->get_type();
$id = $bc->get_id();
$users = $bc->get_plan()->get_setting('users')->get_value();
$anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
$filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
$bc->get_plan()->get_setting('filename')->set_value($filename);

// Execution.
$bc->finish_ui();
$bc->execute_plan();
$results = $bc->get_results();
$file = $results['backup_destination']; // May be empty if file already moved to target location.


//---------------------- manual filename ----------------------------------------------
//$backupdateformat = str_replace(' ', '_', get_string('backupnameformat', 'langconfig'));
// NOTE: we want to force the backups to have the same timestamp in spite of different lang formats
$backupdateformat = str_replace(' ', '_', "%Y%m%d-%H%M");
$date = userdate(time(), $backupdateformat, 99, false);
$date = core_text::strtolower(trim(clean_filename($date), '_'));
$newname = "";
$newname .= $date;
$newname .= '.mbz';
if(!isset($options['quizid'])) $options['quizid']=quizid($options['cmid']);
$filename = "backup_quiz_".$options['quizid']."_".$newname;
//-------------------------------------------------------------------------------------


// Do we need to store backup somewhere else?
if (!empty($dir)) {
    if ($file) {
        mtrace("Writing " . $dir.'/'.$filename);
        if ($file->copy_content_to($dir.'/'.$filename)) {
            $file->delete();
            mtrace("Backup completed.");
        } else {
            mtrace("Destination directory does not exist or is not writable. Leaving the backup in the course backup file area.");
        }
    }
} else {
    mtrace("Backup completed, the new file is listed in the backup area of the given course");
}
$bc->destroy();
exit(0);
