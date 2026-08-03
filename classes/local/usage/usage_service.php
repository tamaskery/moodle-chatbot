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
namespace block_courseaiguide\local\usage;

use block_courseaiguide\local\config\site_config;

defined('MOODLE_INTERNAL') || die();

/**
 * Course/day aggregate usage writer.
 */
final class usage_service {
    /**
     * Record one aggregate outcome without participant identifiers or text.
     *
     * @param int $courseid
     * @param string $mode
     * @param int $latencyms
     */
    public function record(int $courseid, string $mode, int $latencyms): void {
        global $DB;
        if (!(new site_config())->statistics_enabled()) {
            return;
        }
        $daystart = intdiv(time(), DAYSECS) * DAYSECS;
        $factory = \core\lock\lock_config::get_lock_factory('block_courseaiguide_usage');
        $lock = $factory->get_lock($courseid . ':' . $daystart, 2);
        if (!$lock) {
            return;
        }
        try {
            $record = $DB->get_record('block_courseaiguide_usage', [
                'courseid' => $courseid,
                'daystart' => $daystart,
            ]);
            if (!$record) {
                $record = (object) [
                    'courseid' => $courseid,
                    'daystart' => $daystart,
                    'requests' => 0,
                    'errors' => 0,
                    'notfound' => 0,
                    'latencytotal' => 0,
                    'timemodified' => time(),
                ];
                $record->id = $DB->insert_record('block_courseaiguide_usage', $record);
            }
            $record->requests++;
            $record->errors += $mode === 'error' ? 1 : 0;
            $record->notfound += $mode === 'notfound' ? 1 : 0;
            $record->latencytotal += max(0, $latencyms);
            $record->timemodified = time();
            $DB->update_record('block_courseaiguide_usage', $record);
        } finally {
            $lock->release();
        }
    }
}
