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
 * TODO describe file upload
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_bulkemail\form\upload_form;
use local_bulkemail\local\uploaduser;
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_bulkemail_upload');

require_login();

$url = new moodle_url('/local/bulkemail/upload.php', []);
$PAGE->set_url($url);

$fromform = new upload_form();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_bulkemail'));
if ($data = $fromform->get_data()) {
    $userupload = new uploaduser();
    $file = $userupload->get_uploaded_file($data->userfile);
    $userupload->process_upload_file($file, $data->encoding, $data->delimiter_name, $data);
} else {
    echo $OUTPUT->single_button(
        new moodle_url('/local/bulkemail/index.php'),
        get_string('viewuploaded', 'local_bulkemail'),
        'get',
        ['class' => 'row justify-content-end']
    );
    $fromform->display();
}
echo $OUTPUT->footer();
