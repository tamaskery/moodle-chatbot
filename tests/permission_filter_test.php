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
 * Permission-boundary tests.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\retrieval\permission_filter;

/**
 * Tests that indexed source metadata never bypasses current Moodle access rules.
 *
 * @covers \block_courseaiguide\local\retrieval\permission_filter
 */
final class permission_filter_test extends \advanced_testcase {
    /** Index generation used by ready test configurations. */
    private const GENERATION = 7;

    /**
     * A currently visible source is authorised.
     */
    public function test_visible_activity_is_authorised(): void {
        [$course, $student, $page] = $this->create_environment();

        $source = (new permission_filter())->authorise(
            $this->candidate($course, $page),
            $course->id,
            $student->id
        );

        $this->assertNotNull($source);
        $this->assertSame('Visible page', $source['title']);
    }

    /**
     * A hidden activity is rejected even when it remains in the index.
     */
    public function test_hidden_activity_is_rejected(): void {
        [$course, $student, $page] = $this->create_environment([], ['visible' => 0]);

        $this->assertNull((new permission_filter())->authorise(
            $this->candidate($course, $page),
            $course->id,
            $student->id
        ));
    }

    /**
     * An activity restricted to another group is rejected.
     */
    public function test_group_restricted_activity_is_rejected(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $group = $generator->create_group(['courseid' => $course->id]);
        $availability = json_encode(\core_availability\tree::get_root_json([
            \availability_group\condition::get_json($group->id),
        ]));
        $page = $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Group page',
            'content' => 'Restricted group material',
            'contentformat' => FORMAT_HTML,
            'availability' => $availability,
        ]);
        $this->configure_ready_course($course);
        $this->setUser($student);

        $this->assertNull((new permission_filter())->authorise(
            $this->candidate($course, $page),
            $course->id,
            $student->id
        ));
    }

    /**
     * An activity with a future from-date is rejected.
     */
    public function test_date_restricted_activity_is_rejected(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enableavailability = true;
        $availability = json_encode(\core_availability\tree::get_root_json([
            \availability_date\condition::get_json('>=', time() + DAYSECS),
        ]));
        [$course, $student, $page] = $this->create_environment(['availability' => $availability]);

        $this->assertNull((new permission_filter())->authorise(
            $this->candidate($course, $page),
            $course->id,
            $student->id
        ));
    }

    /**
     * A source from an obsolete index generation is rejected.
     */
    public function test_stale_index_generation_is_rejected(): void {
        [$course, $student, $page] = $this->create_environment();
        $candidate = $this->candidate($course, $page);
        $candidate->generation = self::GENERATION - 1;

        $this->assertNull((new permission_filter())->authorise($candidate, $course->id, $student->id));
    }

    /**
     * Candidate course metadata cannot disguise a context from another course.
     */
    public function test_cross_course_context_is_rejected(): void {
        [$course, $student] = $this->create_environment();
        $othercourse = $this->getDataGenerator()->create_course();
        $otherpage = $this->getDataGenerator()->create_module('page', [
            'course' => $othercourse->id,
            'name' => 'Other course page',
            'content' => 'Other course material',
            'contentformat' => FORMAT_HTML,
        ]);
        $candidate = $this->candidate($course, $otherpage);

        $this->assertNull((new permission_filter())->authorise($candidate, $course->id, $student->id));
    }

    /**
     * Create a course, enrolled student, Page activity, and ready plugin configuration.
     *
     * @param array $pagedata Additional Page record data.
     * @param array $options Additional module generator options.
     * @return array Course, student, and Page records.
     */
    private function create_environment(array $pagedata = [], array $options = []): array {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $page = $generator->create_module('page', $pagedata + [
            'course' => $course->id,
            'name' => 'Visible page',
            'content' => 'Visible course material',
            'contentformat' => FORMAT_HTML,
        ], $options);
        $this->configure_ready_course($course);
        $this->setUser($student);
        return [$course, $student, $page];
    }

    /**
     * Insert the active block instance and ready course configuration required by the filter.
     *
     * @param \stdClass $course Course record.
     */
    private function configure_ready_course(\stdClass $course): void {
        global $DB;

        $context = \context_course::instance($course->id);
        $block = $this->getDataGenerator()->create_block('courseaiguide', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        $now = time();
        $DB->insert_record('block_courseaiguide_course', (object) [
            'courseid' => $course->id,
            'blockinstanceid' => $block->id,
            'enabled' => 1,
            'participantsenabled' => 1,
            'sourceareas' => '["page"]',
            'instructions' => '',
            'historyenabled' => 0,
            'indexstatus' => 'ready',
            'indexgeneration' => self::GENERATION,
            'confighash' => hash('sha256', 'permission-boundary-test'),
            'contenthash' => hash('sha256', 'indexed-content'),
            'indexerror' => null,
            'timeindexed' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Build the indexed candidate that corresponds to a generated Page.
     *
     * @param \stdClass $course Course record represented by candidate metadata.
     * @param \stdClass $page Page activity record.
     * @return \stdClass Candidate record.
     */
    private function candidate(\stdClass $course, \stdClass $page): \stdClass {
        global $CFG;

        return (object) [
            'sourceid' => 42,
            'courseid' => $course->id,
            'contextid' => \context_module::instance($page->cmid)->id,
            'cmid' => $page->cmid,
            'sectionid' => null,
            'searcharea' => 'mod_page-activity',
            'sourceitem' => $page->id,
            'sourcetype' => 'page',
            'title' => $page->name,
            'url' => $CFG->wwwroot . '/mod/page/view.php?id=' . $page->cmid,
            'generation' => self::GENERATION,
        ];
    }
}
