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
namespace block_courseaiguide\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/** List the current participant's retained conversation headers. */
final class list_conversations extends external_api {
    /**
     * Define parameters for the external function.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'Course id')]);
    }

    /**
     * List conversations owned by the current participant.
     *
     * @param int $courseid Course ID.
     * @return array Conversation summaries.
     */
    public static function execute(int $courseid): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid'));
        $course = get_course($params['courseid']);
        require_login($course);
        self::validate_context(\context_course::instance($course->id));
        (new \block_courseaiguide\local\access\course_guard())->require_participant((int) $course->id, (int) $USER->id);
        return (new \block_courseaiguide\local\history\history_service())->list_owned((int) $course->id, (int) $USER->id);
    }

    /**
     * Define the external function return structure.
     *
     * @return external_multiple_structure The return definition.
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'publictoken' => new external_value(PARAM_ALPHANUMEXT, 'Opaque token'),
            'timecreated' => new external_value(PARAM_INT, 'Created'),
            'timemodified' => new external_value(PARAM_INT, 'Modified'),
            'expiresat' => new external_value(PARAM_INT, 'Expiry'),
        ]));
    }
}
