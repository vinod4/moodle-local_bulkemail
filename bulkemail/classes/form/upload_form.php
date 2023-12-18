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
 * Upload Form
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_bulkemail\form;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/formslib.php');
/**
 * Class upload_form
 *
 * @package    local_bulkemail
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_form extends \moodleform {
    /**
     * Function definition.
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $mform->addElement('hidden',  'delimiter_name');
        $mform->setType('delimiter_name', PARAM_RAW);
        $mform->setDefault('delimiter_name',  'comma');

        $mform->addElement('hidden',  'encoding');
        $mform->setType('encoding', PARAM_RAW);
        $mform->setDefault('encoding',  'UTF-8');
        $this->add_action_buttons(false, get_string('upload'));
    }
}
