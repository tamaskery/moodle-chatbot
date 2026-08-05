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
namespace block_courseaiguide\local\rate;

use block_courseaiguide\local\config\site_config;

/**
 * Database-backed per-user/course rate limiter.
 */
final class rate_limiter {
    /**
     * Consume one request in both configured windows.
     *
     * @param int $courseid
     * @param int $userid
     */
    public function consume(int $courseid, int $userid): void {
        $config = new site_config();
        $this->consume_window($courseid, $userid, 'short', 300, $config->short_rate_limit());
        $this->consume_window($courseid, $userid, 'day', 86400, $config->daily_rate_limit());
    }

    /**
     * Consume a specific window under a narrow Moodle lock.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $type
     * @param int $seconds
     * @param int $limit
     */
    private function consume_window(int $courseid, int $userid, string $type, int $seconds, int $limit): void {
        global $DB;
        $now = time();
        $start = intdiv($now, $seconds) * $seconds;
        $factory = \core\lock\lock_config::get_lock_factory('block_courseaiguide_rate');
        $lock = $factory->get_lock($courseid . ':' . $userid . ':' . $type . ':' . $start, 2);
        if (!$lock) {
            throw new \moodle_exception('error:rateunavailable', 'block_courseaiguide');
        }
        try {
            $conditions = [
                'courseid' => $courseid,
                'userid' => $userid,
                'windowtype' => $type,
                'windowstart' => $start,
            ];
            $record = $DB->get_record('block_courseaiguide_rate', $conditions);
            if (!$record) {
                $record = (object) ($conditions + [
                    'windowend' => $start + $seconds,
                    'requestcount' => 0,
                    'timemodified' => $now,
                ]);
                $record->id = $DB->insert_record('block_courseaiguide_rate', $record);
            }
            if ((int) $record->requestcount >= $limit) {
                throw new \moodle_exception('error:ratelimited', 'block_courseaiguide');
            }
            $record->requestcount = (int) $record->requestcount + 1;
            $record->timemodified = $now;
            $DB->update_record('block_courseaiguide_rate', $record);
        } finally {
            $lock->release();
        }
    }
}
