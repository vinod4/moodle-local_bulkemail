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

namespace local_bulkemail;
use local_bulkemail\local\uploaduser;

/**
 * Tests for Bulk Email Notifications
 *
 * @package    local_bulkemail
 * @category   test
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uploaduser_test extends \advanced_testcase {
    /**
     * test_processfile
     * @return void
     */
    public function test_processfile() : void {
        $this->resetAfterTest(true);

        // Turn off debugging.
        set_debugging(DEBUG_NONE);

        // Create users.
        $user1 = $this->getDataGenerator()->create_user(['email' => 'user10@mailinator.com']);
        $user2 = $this->getDataGenerator()->create_user(['email' => 'user20@mailinator.com']);
        // Path to the static CSV file.
        $filepath = __DIR__ . '/fixtures/static.csv';

        // Create a file in Moodle's file system.
        $filerecord = array(
            'contextid' => \context_system::instance()->id,
            'component' => 'local_bulkemail',
            'filearea' => 'test',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'static.csv'
        );
        $fs = get_file_storage();
        $file = $fs->create_file_from_pathname($filerecord, $filepath);

        $encoding = 'UTF-8';
        $delimiter = 'comma';
        $formdata = null;
        $returnvalue = true;

        // Call the function with the $file object.
        $uploaduser = new uploaduser();
        $result = $uploaduser->process_upload_file($file, $encoding, $delimiter, $formdata, $returnvalue);

        // Perform assertions on the $result.
        $this->assertEquals($result, 0);
    }
}
