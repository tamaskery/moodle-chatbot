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
 * Incident diagnostic storage tests.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\diagnostic\diagnostic_service;

/**
 * Tests independent diagnostic gates, data minimisation and deletion.
 *
 * @covers \block_courseaiguide\local\diagnostic\diagnostic_service
 */
final class diagnostic_service_test extends \advanced_testcase {
    /**
     * Site enablement, manager arming and per-turn participant consent are all required.
     */
    public function test_all_three_diagnostic_gates_are_required(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $service = new diagnostic_service();
        $result = ['mode' => 'rag', 'answer' => 'Answer', 'facts' => [], 'sources' => []];
        $config = $this->create_course_config($course, time() + HOURSECS, '');

        set_config('diagnosticretentionhours', 0, 'block_courseaiguide');
        $service->capture($config, $user->id, true, 'Question', $result, 'request1');
        set_config('diagnosticretentionhours', 24, 'block_courseaiguide');
        $service->capture($config, $user->id, false, 'Question', $result, 'request2');
        $config->diagnosticuntil = time() - 1;
        $DB->set_field('block_courseaiguide_course', 'diagnosticuntil', $config->diagnosticuntil, [
            'courseid' => $course->id,
        ]);
        $service->capture($config, $user->id, true, 'Question', $result, 'request3');

        $this->assertSame(0, $DB->count_records('block_courseaiguide_diag'));
    }

    /**
     * A consented capture stores reconstructive data but never credentials or a full endpoint.
     */
    public function test_consented_capture_is_bounded_scoped_and_deletable(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        set_config('diagnosticretentionhours', 24, 'block_courseaiguide');
        set_config('endpoint', 'https://provider.example/private/chat/completions', 'block_courseaiguide');
        set_config('model', 'test-model', 'block_courseaiguide');
        set_config('apikey', 'never-store-this-key', 'block_courseaiguide');
        set_config('version', 2026080500, 'block_courseaiguide');
        $service = new diagnostic_service();
        $config = $this->create_course_config($course, time() + HOURSECS, 'Use the course handbook.');
        $receipt = $service->capture($config, $user->id, true, 'What changed?', [
            'mode' => 'rag',
            'answer' => 'The displayed answer.',
            'facts' => [],
            'sources' => [['id' => 7, 'title' => 'Handbook', 'type' => 'page', 'url' => 'https://moodle.test']],
        ], 'abc123', [[
            'content' => 'Filtered reference excerpt.',
            'source' => ['id' => 7, 'title' => 'Handbook', 'type' => 'page'],
        ]]);

        $this->assertTrue($receipt['captured']);
        $record = $DB->get_record('block_courseaiguide_diag', ['requestid' => 'abc123'], '*', MUST_EXIST);
        $this->assertSame('provider.example', $record->providerhost);
        $this->assertSame('test-model', $record->model);
        $this->assertSame(2026080500, (int) $record->pluginversion);
        $this->assertStringContainsString('Filtered reference excerpt', $record->referencejson);
        $serialised = implode(' ', (array) $record);
        $this->assertStringNotContainsString('never-store-this-key', $serialised);
        $this->assertStringNotContainsString('/private/chat/completions', $serialised);
        $this->setUser($manager);
        $this->assertCount(1, $service->list_course($course->id));
        $listdenied = false;
        try {
            $service->list_course($othercourse->id);
        } catch (\required_capability_exception $e) {
            $listdenied = true;
        }
        $this->assertTrue($listdenied, 'A manager cannot inspect diagnostics from another course.');
        $deletedenied = false;
        try {
            $service->delete_record($othercourse->id, $record->id);
        } catch (\required_capability_exception $e) {
            $deletedenied = true;
        }
        $this->assertTrue($deletedenied, 'A manager cannot delete diagnostics from another course.');
        $this->assertTrue($DB->record_exists('block_courseaiguide_diag', ['id' => $record->id]));
        $service->delete_record($course->id, $record->id);
        $this->assertFalse($DB->record_exists('block_courseaiguide_diag', ['id' => $record->id]));
    }

    /**
     * The scheduled retention task removes expired captures and closes expired windows.
     */
    public function test_expired_capture_is_purged(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        set_config('diagnosticretentionhours', 24, 'block_courseaiguide');
        set_config('version', 2026080500, 'block_courseaiguide');
        $config = $this->create_course_config($course, time() + HOURSECS, '');
        $receipt = (new diagnostic_service())->capture($config, $user->id, true, 'Question', [
            'mode' => 'notfound',
            'answer' => 'No answer found.',
            'facts' => [],
            'sources' => [],
        ], 'expiredrequest');
        $this->assertTrue($receipt['captured']);

        $past = time() - 1;
        $DB->set_field('block_courseaiguide_diag', 'expiresat', $past, ['requestid' => 'expiredrequest']);
        $DB->set_field('block_courseaiguide_course', 'diagnosticuntil', $past, ['courseid' => $course->id]);
        (new \block_courseaiguide\task\purge_retained_data())->execute();

        $this->assertFalse($DB->record_exists('block_courseaiguide_diag', ['requestid' => 'expiredrequest']));
        $updatedconfig = $DB->get_record('block_courseaiguide_course', ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertNull($updatedconfig->diagnosticuntil);
    }

    /**
     * Create a complete course configuration for diagnostic gate tests.
     *
     * @param \stdClass $course Course record.
     * @param int $diagnosticuntil Diagnostic window expiry.
     * @param string $instructions Course guidance.
     * @return \stdClass
     */
    private function create_course_config(\stdClass $course, int $diagnosticuntil, string $instructions): \stdClass {
        global $DB;

        $context = \context_course::instance($course->id);
        $block = $this->getDataGenerator()->create_block('courseaiguide', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        $now = time();
        $record = (object) [
            'courseid' => $course->id,
            'blockinstanceid' => $block->id,
            'enabled' => 1,
            'participantsenabled' => 1,
            'sourceareas' => '[]',
            'instructions' => $instructions,
            'historyenabled' => 0,
            'diagnosticuntil' => $diagnosticuntil,
            'indexstatus' => 'ready',
            'indexgeneration' => 1,
            'confighash' => hash('sha256', 'diagnostic-' . $course->id),
            'contenthash' => hash('sha256', 'diagnostic-content-' . $course->id),
            'timeindexed' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('block_courseaiguide_course', $record);
        return $record;
    }
}
