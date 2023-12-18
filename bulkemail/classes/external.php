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
 * External functions
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_bulkemail;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/externallib.php');

use context_system;

/**
 * External class.
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api {
    /**
     * Describes the parameters for bulkemail_view_parameters
     * @return external_function_parameters
     */
    public static function bulkemail_view_parameters() {
        return new \external_function_parameters([
            'options' => new \external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new \external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new \external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new \external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new \external_value(PARAM_INT, 'contextid'),
            'filterdata' => new \external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }

    /**
     * Gets the list of users with their email sent status and date.
     * @param  [array]  $options
     * @param  [array]  $dataoptions
     * @param  integer $offset
     * @param  integer $limit
     * @param  [int]  $contextid
     * @param  [array]  $filterdata
     * @return [array]
     */
    public static function bulkemail_view(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata
    ) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/local/bulkemail/lib.php');
        require_login();

        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::bulkemail_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = true;
        $stable->viewname = $decodedata->viewname;
        $totalusers = bulkemail_count($stable, $filtervalues);
        $totalcount = $totalusers['totalusers'];

        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $stable->viewname = $decodedata->viewname;
        $data = bulkemail_content($stable, $filtervalues);

        return [
            'totalcount' => $totalcount,
            'records' => $data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
            'siteadmin' => $siteadmin
        ];
    }

    /**
     * Describes the bulkemail_view_returns return value.
     * @return external_single_structure
     */
    public static function bulkemail_view_returns() {
        return new \external_single_structure([
            'options' => new \external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new \external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new \external_value(PARAM_INT, 'total number of users in result set'),
            'filterdata' => new \external_value(PARAM_RAW, 'The data for the service'),
            'records' => new \external_multiple_structure(
                new \external_single_structure(
                    array(
                        'id' => new \external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                        'userid' => new \external_value(PARAM_INT, 'userid', VALUE_OPTIONAL),
                        'firstname' => new \external_value(PARAM_RAW, 'firstname', VALUE_OPTIONAL),
                        'lastname' => new \external_value(PARAM_RAW, ' lastname', VALUE_OPTIONAL),
                        'email' => new \external_value(PARAM_RAW, 'email', VALUE_OPTIONAL),
                        'status' => new \external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                        'timecreated' => new \external_value(PARAM_RAW, 'date', VALUE_OPTIONAL),
                    )
                )
            )
        ]);
    }
}
