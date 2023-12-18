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
 * TODO describe file lib
 *
 * @package    local_bulkemail
 * @copyright  2023 Vinod Kumar Aleti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Format the data in a table
 * @param  [stdclass] $stable object
 * @param  [stdclass] $filterdata object
 * @return [array] table data
 */
function bulkemail_content($stable, $filterdata) {
    global $DB;
    $users = bulkemail_count($stable, $filterdata);
    $userslist = $users['users'];
    $data = array();

    foreach ($userslist as $user) {
        $list = array();
        $list['id'] = $user->id;
        $list['userid'] = $user->userid;
        $list['firstname'] = $user->firstname;
        $list['lastname'] = $user->lastname;
        $list['email'] = $user->email;
        if (!empty($user->emailsent)) {
            $user->emailsent = 'Email Sent';
        } else {
            $user->emailsent = 'Email Not sent';
        }
        $list['status'] = $user->emailsent;
        $list['timecreated'] = date('d M Y', $user->timecreated);

        $data[] = $list;
    }
    return $data;
}

/**
 * Fetch the records from database
 * @param  [stdclass] $stable object
 * @param  [stdclass] $filter object
 * @return [array] totalcount and data
 */
function bulkemail_count($stable, $filter) {
    global $DB;

    $countsql = "SELECT count(id) ";
    $selectsql = "SELECT *, emailsent as status";
    $formsql   = " FROM {local_bulkemail} WHERE 1 = 1 ";

    // For "Global (search box)" filter.
    if (isset($filter->search_query) && trim($filter->search_query) != '') {
        $filteredvalues = array_filter(explode(',', $filter->search_query));
        $temparray = array();
        if (!empty($filteredvalues)) {
            foreach ($filteredvalues as $tkey => $tvalue) {
                $temparray[] = "firstname LIKE '%" . trim($tvalue) . "%'";
                $temparray[] = "lastname LIKE '%" . trim($tvalue) . "%'";
                $temparray[] = "email LIKE '%" . trim($tvalue) . "%'";
            }
            $imploderequests = implode(' OR ', $temparray);
            $formsql .= " AND ($imploderequests)";
        }
    }

    $params = array();

    $ordersql = " ORDER BY id DESC";
    $totalusers = $DB->count_records_sql($countsql . $formsql, $params);
    $users = $DB->get_records_sql($selectsql . $formsql . $ordersql, $params, $stable->start, $stable->length);
    return array('totalusers' => $totalusers, 'users' => $users);
}
