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
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled stale-index reconciliation.
 */
final class reconcile_indexes extends \core\task\scheduled_task {
    /** @return string */
    public function get_name(): string {
        return get_string('task:reconcile', 'block_courseaiguide');
    }

    /** Execute task. */
    public function execute(): void {
        global $DB;
        $cutoff = time() - 21600;
        $select = 'enabled = 1 AND (indexstatus <> :ready OR timeindexed IS NULL OR timeindexed < :cutoff)';
        $records = $DB->get_records_select('block_courseaiguide_course', $select,
            ['ready' => 'ready', 'cutoff' => $cutoff], 'timemodified ASC', 'courseid', 0, 100);
        foreach ($records as $record) {
            \block_courseaiguide\local\lifecycle::queue_index((int) $record->courseid);
        }
    }
}
