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

/**
 * Ask the course guide.
 */
final class ask extends external_api {
    /**
     * Define parameters for the external function.
     *
     * @return external_function_parameters The parameter definition.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Validated course id'),
            'question' => new external_value(PARAM_TEXT, 'Bounded plain-text question'),
            'savehistory' => new external_value(PARAM_BOOL, 'Explicitly save this turn', VALUE_DEFAULT, false),
            'conversationid' => new external_value(PARAM_ALPHANUMEXT, 'Owned opaque conversation token', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $courseid
     * @param string $question
     * @param bool $savehistory
     * @param string $conversationid
     * @return array
     */
    public static function execute(
        int $courseid,
        string $question,
        bool $savehistory = false,
        string $conversationid = ''
    ): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'courseid',
            'question',
            'savehistory',
            'conversationid'
        ));
        require_sesskey();
        $course = get_course($params['courseid']);
        require_login($course);
        self::validate_context(\context_course::instance($course->id));
        return (new \block_courseaiguide\local\chat\orchestrator())->ask(
            (int) $course->id,
            (int) $USER->id,
            $params['question'],
            $params['savehistory'],
            $params['conversationid']
        );
    }

    /**
     * Define the external function return structure.
     *
     * @return external_single_structure The return definition.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'mode' => new external_value(PARAM_ALPHA, 'structured, rag, notfound, or error'),
            'answer' => new external_value(PARAM_RAW, 'Bounded plain-text answer'),
            'facts' => new external_multiple_structure(new external_single_structure([
                'type' => new external_value(PARAM_ALPHANUMEXT, 'Fact type'),
                'label' => new external_value(PARAM_TEXT, 'Fact label'),
                'value' => new external_value(PARAM_TEXT, 'Fact value'),
                'url' => new external_value(PARAM_URL, 'Moodle-owned URL'),
            ])),
            'sources' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Server source id'),
                'title' => new external_value(PARAM_TEXT, 'Source title'),
                'type' => new external_value(PARAM_ALPHANUMEXT, 'Source type'),
                'url' => new external_value(PARAM_URL, 'Server-validated Moodle URL'),
            ])),
            'conversationid' => new external_value(PARAM_ALPHANUMEXT, 'Owned opaque token or empty'),
            'requestid' => new external_value(PARAM_ALPHANUMEXT, 'Non-sensitive request id'),
        ]);
    }
}
