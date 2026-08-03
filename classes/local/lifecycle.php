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
namespace block_courseaiguide\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Scoped lifecycle operations.
 */
final class lifecycle {
    /**
     * Queue one course indexing task.
     *
     * @param int $courseid
     */
    public static function queue_index(int $courseid): void {
        global $DB;
        if (!$DB->record_exists('block_courseaiguide_course', ['courseid' => $courseid, 'enabled' => 1])) {
            return;
        }
        $task = new \block_courseaiguide\task\index_course();
        $task->set_custom_data(['courseid' => $courseid]);
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Purge all plugin data for a course.
     *
     * @param int $courseid
     */
    public static function purge_course(int $courseid): void {
        global $DB;
        $DB->delete_records('block_courseaiguide_msg', ['courseid' => $courseid]);
        $DB->delete_records('block_courseaiguide_conv', ['courseid' => $courseid]);
        $DB->delete_records('block_courseaiguide_usage', ['courseid' => $courseid]);
        $DB->delete_records('block_courseaiguide_rate', ['courseid' => $courseid]);
        $DB->delete_records('block_courseaiguide_chunk', ['courseid' => $courseid]);
        $DB->delete_records('block_courseaiguide_source', ['courseid' => $courseid]);
        $DB->delete_records('block_courseaiguide_course', ['courseid' => $courseid]);
    }

    /**
     * Delete one module source and its chunks.
     *
     * @param int $courseid
     * @param int $cmid
     */
    public static function purge_module(int $courseid, int $cmid): void {
        global $DB;
        $sources = $DB->get_records('block_courseaiguide_source', ['courseid' => $courseid, 'cmid' => $cmid], '', 'id');
        foreach ($sources as $source) {
            $DB->delete_records('block_courseaiguide_chunk', ['sourceid' => $source->id]);
        }
        $DB->delete_records('block_courseaiguide_source', ['courseid' => $courseid, 'cmid' => $cmid]);
        $DB->set_field('block_courseaiguide_course', 'indexstatus', 'stale', ['courseid' => $courseid]);
    }
}
