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
 * Citation mapping security tests.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\chat\citation_mapper;

/**
 * Tests that model-selected source IDs are allowlisted and re-authorised.
 *
 * @covers \block_courseaiguide\local\chat\citation_mapper
 */
final class citation_mapper_test extends \advanced_testcase {
    /**
     * Hidden, stale, unknown, duplicated, and non-retrieved source IDs are discarded.
     */
    public function test_only_currently_authorised_allowlisted_sources_are_mapped(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $visible = $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Visible citation',
            'content' => 'Visible material',
            'contentformat' => FORMAT_HTML,
        ]);
        $hidden = $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Hidden citation',
            'content' => 'Hidden material',
            'contentformat' => FORMAT_HTML,
        ], ['visible' => 0]);
        $notretrieved = $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Not retrieved citation',
            'content' => 'Other material',
            'contentformat' => FORMAT_HTML,
        ]);
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
            'sourceareas' => '["page"]',
            'instructions' => '',
            'historyenabled' => 0,
            'indexstatus' => 'ready',
            'indexgeneration' => 3,
            'confighash' => hash('sha256', 'citation-test'),
            'contenthash' => hash('sha256', 'citation-content'),
            'timeindexed' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $visibleid = $this->insert_source($course, $visible, 3, 'visible');
        $hiddenid = $this->insert_source($course, $hidden, 3, 'hidden');
        $staleid = $this->insert_source($course, $visible, 2, 'stale');
        $notretrievedid = $this->insert_source($course, $notretrieved, 3, 'notretrieved');
        $this->setUser($student);

        $sources = (new citation_mapper())->map(
            $course->id,
            $student->id,
            [$visibleid, $hiddenid, $staleid],
            [$visibleid, $hiddenid, $staleid, $notretrievedid, 999999, $visibleid]
        );

        $this->assertCount(1, $sources);
        $this->assertSame($visibleid, $sources[0]['id']);
        $this->assertSame('Visible citation', $sources[0]['title']);
        $this->assertSame($CFG->wwwroot . '/mod/page/view.php?id=' . $visible->cmid, $sources[0]['url']);
    }

    /**
     * Insert one indexed Page source.
     *
     * @param \stdClass $course Course record.
     * @param \stdClass $page Page activity record.
     * @param int $generation Index generation.
     * @param string $key Unique test key.
     * @return int Source ID.
     */
    private function insert_source(\stdClass $course, \stdClass $page, int $generation, string $key): int {
        global $CFG, $DB;

        $now = time();
        return $DB->insert_record('block_courseaiguide_source', (object) [
            'courseid' => $course->id,
            'contextid' => \context_module::instance($page->cmid)->id,
            'cmid' => $page->cmid,
            'sectionid' => null,
            'searcharea' => 'mod_page-activity',
            'sourceitem' => $page->id,
            'sourcekey' => hash('sha256', $key),
            'sourcetype' => 'page',
            'title' => $page->name,
            'url' => $CFG->wwwroot . '/mod/page/view.php?id=' . $page->cmid,
            'contenthash' => hash('sha256', $key . '-content'),
            'accesshash' => hash('sha256', $key . '-access'),
            'generation' => $generation,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
