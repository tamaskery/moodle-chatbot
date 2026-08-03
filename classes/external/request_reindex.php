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
namespace block_courseaiguide\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

/**
 * Queue a manager-requested course re-index.
 */
final class request_reindex extends external_api {
    /** @return external_function_parameters */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'Course id')]);
    }

    /** @param int $courseid @return array */
    public static function execute(int $courseid): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid'));
        require_sesskey();
        $course = get_course($params['courseid']);
        require_login($course);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('block/courseaiguide:manage', $context);
        \block_courseaiguide\local\lifecycle::queue_index((int) $course->id);
        return ['queued' => true];
    }

    /** @return external_single_structure */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(['queued' => new external_value(PARAM_BOOL, 'Queued')]);
    }
}
