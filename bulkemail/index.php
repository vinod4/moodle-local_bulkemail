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
 * TODO describe file index
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
global $OUTPUT, $PAGE;
admin_externalpage_setup('local_bulkemail_upload');
require_login();
$PAGE->set_url('/local/bulkemail/index.php');

$renderer = $PAGE->get_renderer('local_bulkemail');
$filterparams = $renderer->bulkemail_view(true);
$filterparams['submitid'] = 'form#filteringform';

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_bulkemail'));
echo $OUTPUT->render_from_template('local_bulkemail/global_filter', $filterparams);
echo $renderer->bulkemail_view($filterdata);
echo $OUTPUT->footer();
