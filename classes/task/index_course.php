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
 * Ad-hoc course indexing task.
 */
final class index_course extends \core\task\adhoc_task {
    /** Execute task. */
    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $courseid = (int) ($data->courseid ?? 0);
        if (!$courseid) {
            return;
        }
        $factory = \core\lock\lock_config::get_lock_factory('block_courseaiguide');
        $lock = $factory->get_lock('course:' . $courseid, 5);
        if (!$lock) {
            throw new \moodle_exception('error:indexlock', 'block_courseaiguide');
        }
        try {
            (new \block_courseaiguide\local\index\indexer())->index_course($courseid);
        } catch (\Throwable $e) {
            if ($DB->record_exists('block_courseaiguide_course', ['courseid' => $courseid])) {
                $DB->set_field('block_courseaiguide_course', 'indexstatus', 'failed', ['courseid' => $courseid]);
                $DB->set_field('block_courseaiguide_course', 'indexerror', 'index_failed', ['courseid' => $courseid]);
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
