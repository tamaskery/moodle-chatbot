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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Course AI Guide plugin.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Course AI Guide contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\history\history_service;

defined('MOODLE_INTERNAL') || die();

/** Tests for optional three-gate conversation storage. */
final class history_service_test extends \advanced_testcase {
    /** No-store mode must create no personal message records. */
    public function test_no_store_default_creates_no_records(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        set_config('retentiondays', 0, 'block_courseaiguide');
        $token = (new history_service())->store_turn((object) [
            'courseid' => $course->id,
            'historyenabled' => 1,
        ], $user->id, true, '', 'Question', 'Answer', 'request1');
        $this->assertSame('', $token);
        $this->assertFalse($DB->record_exists('block_courseaiguide_conv', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('block_courseaiguide_msg', ['userid' => $user->id]));
    }

    /** All three gates allow participant-owned storage and deletion. */
    public function test_opted_in_turn_is_owned_and_deletable(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        set_config('retentiondays', 7, 'block_courseaiguide');
        $service = new history_service();
        $token = $service->store_turn((object) [
            'courseid' => $course->id,
            'historyenabled' => 1,
        ], $user->id, true, '', 'Question', 'Answer', 'request1');
        $this->assertNotSame('', $token);
        $this->assertCount(2, $service->get_owned($course->id, $user->id, $token));
        $this->assertSame([], $service->get_owned($course->id, $user->id + 1, $token));
        $service->delete_owned($course->id, $user->id, $token);
        $this->assertFalse($DB->record_exists('block_courseaiguide_conv', ['publictoken' => $token]));
        $this->assertFalse($DB->record_exists('block_courseaiguide_msg', ['userid' => $user->id]));
    }

    /** Missing participant opt-in must prevent writes. */
    public function test_participant_opt_in_is_required(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        set_config('retentiondays', 7, 'block_courseaiguide');
        (new history_service())->store_turn((object) [
            'courseid' => $course->id,
            'historyenabled' => 1,
        ], $user->id, false, '', 'Question', 'Answer', 'request1');
        $this->assertEquals(0, $DB->count_records('block_courseaiguide_conv'));
    }
}
