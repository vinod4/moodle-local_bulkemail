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
 * Defines the renderer for the Bulkemail feature.
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_bulkemail\output;

defined('MOODLE_INTERNAL') || die();

use context_system;

/**
 * Class renderer
 */
class renderer extends \plugin_renderer_base {

    /**
     * Displays the bulk email sent data
     * @param  boolean $filter
     * @return [string] actual data
     */
    public function bulkemail_view($filter = false) {
        global $USER;

        $systemcontext = context_system::instance();

        $options = array('targetID' => 'customtabledisplay', 'perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');

        $options['methodName'] = 'local_bulkemail_uplodedmailview';
        $options['templateName'] = 'local_bulkemail/bulkemail_view';

        $options = json_encode($options);

        $dataoptions = json_encode(array('userid' => $USER->id, 'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
            'targetID' => 'customtabledisplay',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];

        if ($filter) {
            return $context;
        } else {
            return $this->render_from_template('local_bulkemail/cardPaginate', $context);
        }
    }
}
