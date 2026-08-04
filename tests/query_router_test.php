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
 * Course AI Guide plugin.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\structured\query_router;

/**
 * Tests for deterministic user-specific Moodle facts.
 *
 * @covers \block_courseaiguide\local\structured\query_router
 */
final class query_router_test extends \advanced_testcase {
    /**
     * Broad deadline questions should return visible activities in chronological order.
     */
    public function test_broad_deadlines_are_visible_bounded_moodle_facts(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $later = time() + (4 * DAYSECS);
        $earlier = time() + (2 * DAYSECS);
        $generator->create_module('assign', [
            'course' => $course->id,
            'name' => 'Later report',
            'duedate' => $later,
        ]);
        $generator->create_module('assign', [
            'course' => $course->id,
            'name' => 'Earlier report',
            'duedate' => $earlier,
        ]);
        $generator->create_module('assign', [
            'course' => $course->id,
            'name' => 'Hidden report',
            'duedate' => time() + DAYSECS,
        ], ['visible' => 0]);

        $result = (new query_router())->answer($course->id, $student->id, 'What deadlines do I have?');

        $this->assertSame('structured', $result['mode']);
        $this->assertCount(2, $result['facts']);
        $this->assertCount(2, $result['sources']);
        $this->assertStringContainsString('Earlier report', $result['facts'][0]['label']);
        $this->assertStringContainsString('Later report', $result['facts'][1]['label']);
        $this->assertStringNotContainsString('Hidden report', $result['answer']);
        $this->assertSame(userdate($earlier), $result['facts'][0]['value']);
        $this->assertSame(userdate($later), $result['facts'][1]['value']);
    }

    /**
     * An explicit activity question should preserve the detailed single-activity response.
     */
    public function test_named_activity_keeps_detailed_dates(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $due = time() + (3 * DAYSECS);
        $generator->create_module('assign', [
            'course' => $course->id,
            'name' => 'Capstone report',
            'duedate' => $due,
        ]);

        $result = (new query_router())->answer($course->id, $student->id, 'When is the Capstone report due?');

        $this->assertSame('structured', $result['mode']);
        $this->assertCount(1, $result['facts']);
        $this->assertCount(1, $result['sources']);
        $this->assertStringContainsString('Capstone report', $result['answer']);
        $this->assertSame(userdate($due), $result['facts'][0]['value']);
    }
}
