<?php
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
 * Upload User emails
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_bulkemail\local;

use context_user;
use context_system;
use csv_import_reader;
use core_text;
use lang_string;
use moodle_exception;
use stdclass;
use html_table;
use html_table_cell;
use html_writer;
use html_table_row;
use moodle_url;
/**
 * class uploaduser
 *
 * @package    local_bulkemail
 * @copyright  2023 your name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html gnu gpl v3 or later
 */
class uploaduser {
    /** @var integer Error count */
    protected $errorcount = 0;

    /** @var integer Uploaded Columns */
    protected $columns;

    /** @var array Columns mapping */
    protected $columnsmapping = array();

    /** @var array Allowed columns in the CSV upload */
    protected $allowedcolumns = array(
        'firstname',
        'lastname',
        'email',
    );

    /** @var integer Errors in the upload */
    protected $errors = 0;

    /** @var integer Success data count */
    protected $succeded = 0;

    /** @var array Users uploaded data */
    private $users = array();

    /** @var array Error messages */
    private $errormessages = array();

    /**
     * process the uploaded csv file, validate data, and initiate coin upload.
     * @param  [mixed] $file the uploaded csv file or its content
     * @param  [string] $encoding the encoding of the csv file
     * @param  [string] $delimiter the delimiter used in the csv file
     * @param  [stdclass] $formdata the form data submitted along with the file
     * @param  boolean $returnvalue [description]
     * @return [int] errorcount
     */
    public function process_upload_file($file, $encoding, $delimiter, $formdata = null, $returnvalue = false) {
        global $CFG, $DB, $PAGE, $OUTPUT;
        require_once($CFG->libdir . '/csvlib.class.php');

        if (is_object($file)) {
            $content = $file->get_content();
        } else {
            $content = file_get_contents($file);
        }

        $uploadid = csv_import_reader::get_new_iid('uploadcoins');
        $cir = new csv_import_reader($uploadid, 'uploadcoins');

        $readcount = $cir->load_csv_content($content, $encoding, $delimiter);

        unset($content);
        if (!$readcount) {
            throw new moodle_exception('csvloaderror', 'error', $PAGE->url, $cir->get_error());
        }

        $this->columns = $cir->get_columns();

        $this->validate_columns();

        $cir->init();
        $rownum = 0;

        $haserrors = false;

        $errorcount = 0;

        while ($row = $cir->next()) {
            $rownum++;
            $this->users[$rownum] = array(
                'fields' => array(),
                'errors' => array(),
                'data' => array(),
            );

            $hash = array();
            foreach ($row as $i => $value) {
                if (!isset($this->columnsmapping[$i])) {
                    continue;
                }
                $value = $this->clean_users_data($this->columnsmapping[$i], $value, $rownum);

                if ($this->columnsmapping[$i]) {
                    $hash[$this->columnsmapping[$i]] = $value;
                }
            }

            if (!empty($hash)) {
                $this->users[$rownum]['data'] = $hash;
            }

            if (!empty($this->users[$rownum]['errors'])) {
                $errorcount++;
            }

            $haserrors = $haserrors || !empty($this->users[$rownum]['errors']);
        }

        // Update the error count in $stats.
        $stats = new stdclass();
        $stats->errors = $errorcount;
        $stats->success = $this->succeded;

        if (!empty($this->users[0]['errors'])) {
            foreach ($this->users[0]['errors'] as $msg) {
                echo $OUTPUT->notification($msg, \core\output\notification::notify_error);
                $url = new moodle_url('/local/bulkemail/index.php');
                echo html_writer::link($url, get_string('continue'), ['class' => 'btn btn-primary']);
            }
            return;
        }

        $this->upload_users($formdata);

        if ($returnvalue) {
            return $errorcount;
        }

        if (is_object($file)) {
            $this->preview_uploaded($formdata);
        }
    }

    /**
     * Display a preview of the uploaded coins data.
     * @param  [stdclass] $formdata the form data submitted along with the file
     * @return null
     */
    protected function preview_uploaded($formdata) {
        global $OUTPUT;

        if (empty($this->users)) {
            return;
        }

        // Generate a unique session key based on file details.
        $processedkey = 'uploadcoins_processed_' . md5(serialize($formdata));

        // Check if data has already been processed.
        if (empty($_SESSION[$processedkey])) {
            // Initialize reload count if not set.
            $reloadcountkey = 'uploadcoins_reload_count_' . md5(serialize($formdata));
            $_SESSION[$reloadcountkey] = 0;
        }

        // Check if reload count is reached.
        $reloadcountkey = 'uploadcoins_reload_count_' . md5(serialize($formdata));
        $reloadcount = $_SESSION[$reloadcountkey];

        if ($reloadcount >= 1) {
            echo $OUTPUT->notification(get_string('data_already_processed', 'local_bulkemail'), 'info');
            $url = new moodle_url('/local/bulkemail/upload.php');
            redirect($url);
        }

        // Increment reload count for the current upload.
        $reloadcount++;
        $_SESSION[$reloadcountkey] = $reloadcount;

        $stats = new stdclass();
        $stats->errors = $this->errors;
        $stats->success = $this->succeded;

        // Create a sorted array of previewed rows to maintain order.
        $previewdrows = $this->get_previewed_rows();
        $previewdrows = array_unique($previewdrows); // Remove duplicates (if any).
        sort($previewdrows); // Sort the array to maintain order.

        // Create and display the preview table.
        $table = new html_table();
        $table->id = 'previewcoins';
        $table->attributes = ['data-stable' => 'plain'];

        // Add column names to the preview table.
        $table->head = array();
        foreach ($this->columnsmapping as $key => $value) {
            $table->head[] = ucwords(str_replace("_", " ", $value));
        }
        $table->head[] = get_string('status', 'local_bulkemail');

        // Add (some) rows to the preview table.
        $table->data = array();
        foreach ($previewdrows as $idx) {
            $line = $this->users[$idx];
            $cells = array();

            $hasemptycell = false;

            foreach ($this->columnsmapping as $value) {
                $text = s($line['data'][$value]);
                $cells[] = new html_table_cell($text);
            }

            $text = '';
            if ($line['errors']) {
                $text .= html_writer::div(join('<br>', $line['errors']), 'notifyproblem');
            } else {
                // Check for empty cells in data.
                foreach ($this->columnsmapping as $value) {
                    if (empty($line['data'][$value])) {
                        $columnname = ucwords(str_replace("_", " ", $value));
                        $hasemptycell = true;
                        break;
                    }
                }

                if (!$hasemptycell) {
                    $text .= html_writer::div(get_string('successupload', 'local_bulkemail'), 'notifysuccess');
                }
            }

            $cells[] = new html_table_cell($text);
            $table->data[] = new html_table_row($cells);
        }
        echo html_writer::table($table);

        $url = new moodle_url('/local/bulkemail/upload.php');
        echo html_writer::link($url, get_string('continue'), ['class' => 'btn btn-primary thankyou-continue']);

        $_SESSION[$processedkey] = true;
    }

    /**
     * function clean_users_data
     * @param string $key the key representing the column in the coins data.
     * @param mixed $value the value of the column in the coins data.
     * @param int $rownum the row number being processed.
     * @return mixed|null the cleaned and validated value or null in case of an error.
     */
    protected function clean_users_data($key, $value, $rownum) {
        // Check for empty values and display an error.
        if (empty($value)) {
            $columnname = ucwords(str_replace("_", " ", $key));
            // Store the error message.
            $this->users[$rownum]['errors'][] = get_string('error_empty_cell', 'local_bulkemail', ['column' => $columnname]);
            return null;
        }
        if ($key == 'email') {
            // Example validation: ensure that tenantid is a positive integer.
            if (!validate_email($value)) {
                $this->users[$rownum]['errors'][] = get_string('emailnotvalid', 'local_bulkemail');
                $this->users[$rownum]['fields'][] = 'email';
                $this->errorcount++;
            }
            if (!$this->get_user_by_email($value)) {
                $this->users[$rownum]['errors'][] = get_string('userdoesnotexists', 'local_bulkemail');
                $this->users[$rownum]['fields'][] = 'email';
                $this->errorcount++;
            }
        }
        return clean_param($value, PARAM_RAW);
    }

    /**
     * get_user_by_email
     * @param string $email - user email address.
     * @return object $user object.
     */
    private function get_user_by_email($email) {
        global $DB;
        return $DB->get_record('user', ['email' => $email]);
    }

    /**
     * validate_columns
     * TODO: validates CSV uploaded columns.
     */
    private function validate_columns() {
        global $PAGE;
        foreach ($this->columns as $i => $columnname) {
            if (in_array(strtolower($columnname), $this->allowedcolumns)) {
                $this->columnsmapping[$i] = strtolower($columnname);
            } else {
                throw new moodle_exception('invalidcolumnname', 'local_bulkemail', $PAGE->url, $columnname);
            }
        }
    }

    /**
     * function upload_users
     * @param stdclass $formdata - uploaded users data.
     * @throws moodle_exception throws an exception id users data is not processed.
     */
    public function upload_users($formdata) {
        global $DB, $USER;

        if (empty($this->users)) {
            throw new moodle_exception('csvemptyfile', 'error');
        }

        // Generate a unique session key based on file details.
        $processedkey = 'uploadcoins_processed_' . md5(serialize($formdata));

        // Check if data has already been processed.
        if (empty($_SESSION[$processedkey])) {
            // Initialize reload count if not set.
            $reloadcountkey = 'uploadcoins_reload_count_' . md5(serialize($formdata));
            $_SESSION[$reloadcountkey] = 0;
        }

        // Check if reload count is reached.
        $reloadcountkey = 'uploadcoins_reload_count_' . md5(serialize($formdata));
        $reloadcount = $_SESSION[$reloadcountkey];

        // Check if reload count is reached.
        if ($reloadcount >= 1) {
            // Log a warning if attempting to process data on the second reload.
            debugging("attempt to process data on the second reload. skipping insertion.");
            return;
        }

        // Increment reload count for the current upload.
        $reloadcount++;
        $_SESSION[$reloadcountkey] = $reloadcount;

        foreach ($this->users as $rownum => $users) {
            if (!isset($users['data']) || empty($users['data']) || !empty($users['errors'])) {
                // Skip rows with errors or empty data.
                continue;
            }

            $useremail = $users['data']['email'];
            $user = $this->get_user_by_email($useremail);

            try {
                $uploaddata = new stdclass();
                $uploaddata->userid = $user->id;
                $uploaddata->firstname = $users['data']['firstname'];
                $uploaddata->lastname = $users['data']['lastname'];
                $uploaddata->email = $users['data']['email'];
                $emailsent = email_to_user($user,
                                \core_user::get_support_user(),
                                $subject = get_string('samplesubject', 'local_bulkemail'),
                                $body = get_string('samplebody', 'local_bulkemail'),
                                $html = get_string('samplebody', 'local_bulkemail')
                            );
                $uploaddata->emailsent = $emailsent ? 1 : 0;
                $uploaddata->timecreated = time();
                $uploaddata->usercreated = $user->id;
                $DB->insert_record('local_bulkemail', $uploaddata);
            } catch (exception $e) {
                // Log or handle the exception.
                debugging("error processing row $rownum: " . $e->getmessage());
            }
        }
    }

    /**
     * get_uploaded_file information
     * @param  [int] $draftid draftid of the file
     * @return [string] actual uploaded file
     */
    public function get_uploaded_file($draftid) {
        global $USER;
        if (!$draftid) {
            return null;
        }
        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id desc', false)) {
            return null;
        }
        $file = reset($files);
        return $file;
    }

    /**
     * Find up rows to show in preview     *
     * number of previewed rows is limited but rows with errors and warnings have priority.     *
     * @return array
     */
    protected function get_previewed_rows() {
        global $OUTPUT;
        $previewlimit = 100; // Set the maximum number of rows to display in the preview.

        // Separate rows into those with errors and those without.
        $rowswitherrors = array();
        $rowswithouterrors = array();

        foreach ($this->users as $rownum => $row) {
            if (!empty($row['errors'])) {
                $rowswitherrors[] = $rownum;
            } else {
                $rowswithouterrors[] = $rownum;
            }
        }

        // Prioritize rows with errors, and limit the total number of rows in the preview.
        $previewrows = array_merge(array_slice($rowswitherrors, 0, $previewlimit),
                array_slice($rowswithouterrors, 0, $previewlimit - count($rowswitherrors)));

        // Display statistics.
        $stats = new stdclass();
        $stats->total = count($this->users);
        $stats->errors = count($rowswitherrors);
        $stats->success = count($rowswithouterrors);

        echo $OUTPUT->notification(get_string('uploadstats', 'local_bulkemail', $stats), 'info');

        return $previewrows;
    }
}
