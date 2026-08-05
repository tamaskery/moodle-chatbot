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
 * Course AI Assistant plugin.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\local\diagnostic;

use block_courseaiguide\local\chat\reference_safety;
use block_courseaiguide\local\config\course_config;
use block_courseaiguide\local\config\site_config;

/**
 * Explicit-consent, short-lived incident capture separate from chat history.
 */
final class diagnostic_service {
    /**
     * Store one diagnostic turn only when every independent gate passes.
     *
     * @param \stdClass $courseconfig Course configuration.
     * @param int $userid Participant ID.
     * @param bool $consented Explicit consent for this turn.
     * @param string $question Displayed participant question.
     * @param array $result Final server-validated displayed result.
     * @param string $requestid Non-sensitive support request ID.
     * @param array $chunks Access-filtered chunks used for this turn.
     * @param string $providerdiagnostic Allowlisted provider failure category.
     * @return array Capture receipt with captured and expiresat.
     */
    public function capture(
        \stdClass $courseconfig,
        int $userid,
        bool $consented,
        string $question,
        array $result,
        string $requestid,
        array $chunks = [],
        string $providerdiagnostic = ''
    ): array {
        global $DB;

        $siteconfig = new site_config();
        $hours = $siteconfig->diagnostic_retention_hours();
        $now = time();
        $currentconfig = course_config::get((int) $courseconfig->courseid);
        if (!$consented || !$hours || !$currentconfig || !course_config::diagnostic_active($currentconfig, $now)) {
            return ['captured' => false, 'expiresat' => 0];
        }

        $references = [];
        foreach (array_slice($chunks, 0, 8) as $chunk) {
            $source = (array) ($chunk['source'] ?? []);
            $references[] = [
                'sourceid' => (int) ($source['id'] ?? 0),
                'title' => \core_text::substr(clean_param((string) ($source['title'] ?? ''), PARAM_TEXT), 0, 255),
                'type' => clean_param((string) ($source['type'] ?? ''), PARAM_ALPHANUMEXT),
                'text' => \core_text::substr(clean_param((string) ($chunk['content'] ?? ''), PARAM_TEXT), 0, 12000),
            ];
        }

        $providercalled = !empty($chunks);
        $guidance = $providercalled
            ? clean_param((string) ($courseconfig->instructions ?? ''), PARAM_TEXT)
            : '';
        if (reference_safety::contains_prompt_injection($guidance)) {
            $guidance = '';
        }
        $parts = parse_url($siteconfig->endpoint());
        $expiresat = $now + ($hours * HOURSECS);
        $record = (object) [
            'courseid' => (int) $courseconfig->courseid,
            'userid' => $userid,
            'requestid' => clean_param($requestid, PARAM_ALPHANUMEXT),
            'mode' => clean_param((string) ($result['mode'] ?? 'error'), PARAM_ALPHANUMEXT),
            'question' => \core_text::substr(clean_param($question, PARAM_TEXT), 0, 2000),
            'answer' => \core_text::substr(clean_param((string) ($result['answer'] ?? ''), PARAM_TEXT), 0, 6000),
            'factsjson' => self::encode(array_slice((array) ($result['facts'] ?? []), 0, 20)),
            'sourcesjson' => self::encode(array_slice((array) ($result['sources'] ?? []), 0, 8)),
            'referencejson' => self::encode($references),
            'guidance' => \core_text::substr($guidance, 0, 2000),
            'model' => $providercalled
                ? \core_text::substr(clean_param($siteconfig->model(), PARAM_TEXT), 0, 255)
                : '',
            'providerhost' => $providercalled
                ? \core_text::substr(clean_param((string) ($parts['host'] ?? ''), PARAM_HOST), 0, 255)
                : '',
            'diagnostic' => \core_text::substr(clean_param($providerdiagnostic, PARAM_ALPHANUMEXT), 0, 40),
            'pluginversion' => (int) get_config('block_courseaiguide', 'version'),
            'consentat' => $now,
            'expiresat' => $expiresat,
            'timecreated' => $now,
        ];
        $DB->insert_record('block_courseaiguide_diag', $record);
        return ['captured' => true, 'expiresat' => $expiresat];
    }

    /**
     * List unexpired records for a manager-authorised course.
     *
     * @param int $courseid Course ID.
     * @return array
     */
    public function list_course(int $courseid): array {
        global $DB;
        self::require_manage($courseid);
        return array_values($DB->get_records_select(
            'block_courseaiguide_diag',
            'courseid = :courseid AND expiresat > :now',
            ['courseid' => $courseid, 'now' => time()],
            'timecreated DESC'
        ));
    }

    /**
     * Delete one course-scoped diagnostic record.
     *
     * @param int $courseid Course ID.
     * @param int $recordid Record ID.
     */
    public function delete_record(int $courseid, int $recordid): void {
        global $DB;
        self::require_manage($courseid);
        $DB->delete_records('block_courseaiguide_diag', ['id' => $recordid, 'courseid' => $courseid]);
    }

    /**
     * Delete every diagnostic record for one course.
     *
     * @param int $courseid Course ID.
     */
    public function delete_course(int $courseid): void {
        global $DB;
        self::require_manage($courseid);
        $DB->delete_records('block_courseaiguide_diag', ['courseid' => $courseid]);
    }

    /**
     * Enforce the manager-only diagnostic investigation boundary.
     *
     * @param int $courseid Course ID.
     */
    private static function require_manage(int $courseid): void {
        $context = \context_course::instance($courseid, MUST_EXIST);
        require_capability('block/courseaiguide:manage', $context);
    }

    /**
     * Encode bounded server-controlled structures without throwing.
     *
     * @param array $value Value.
     * @return string
     */
    private static function encode(array $value): string {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? '[]' : $encoded;
    }
}
