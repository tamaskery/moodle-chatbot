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
 * Course access-guard tests.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\access\course_guard;
use block_courseaiguide\local\rate\site_circuit_breaker;

/**
 * Tests fail-closed course access when the site circuit breaker is open.
 *
 * @covers \block_courseaiguide\local\access\course_guard
 */
final class course_guard_test extends \advanced_testcase {
    /**
     * A ready course and authorised student are still denied after the site ceiling is reached.
     */
    public function test_open_site_circuit_denies_otherwise_authorised_course_request(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $context = \context_course::instance($course->id);
        $block = $generator->create_block('courseaiguide', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        $now = time();
        $DB->insert_record('block_courseaiguide_course', (object) [
            'courseid' => $course->id,
            'blockinstanceid' => $block->id,
            'enabled' => 1,
            'participantsenabled' => 1,
            'sourceareas' => '[]',
            'instructions' => '',
            'historyenabled' => 0,
            'indexstatus' => 'ready',
            'indexgeneration' => 1,
            'confighash' => hash('sha256', 'guard-config'),
            'contenthash' => hash('sha256', 'guard-content'),
            'timeindexed' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        set_config('endpoint', 'https://provider.example/v1/chat/completions', 'block_courseaiguide');
        set_config('model', 'test-model', 'block_courseaiguide');
        set_config('apikey', 'test-key', 'block_courseaiguide');
        set_config('siteprovidercalllimit', 1, 'block_courseaiguide');
        $messagesink = $this->redirectMessages();
        (new site_circuit_breaker())->reserve();
        $this->setUser($student);

        try {
            (new course_guard())->require_ask($course->id, $student->id);
            $this->fail('The course guard must deny requests while the site circuit is open.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:sitecircuitbreaker', $e->errorcode);
        }
        $messagesink->close();
    }
}
