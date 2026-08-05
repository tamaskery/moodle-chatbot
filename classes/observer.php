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
namespace block_courseaiguide;

/**
 * Lightweight event observers.
 */
final class observer {
    /**
     * Queue indexing after relevant course content changes.
     *
     * @param \core\event\base $event Moodle event.
     */
    public static function content_changed(\core\event\base $event): void {
        if (!empty($event->courseid)) {
            \block_courseaiguide\local\lifecycle::queue_index((int) $event->courseid);
        }
    }

    /**
     * Remove indexed content after a course module is deleted.
     *
     * @param \core\event\course_module_deleted $event Course-module deletion event.
     */
    public static function module_deleted(\core\event\course_module_deleted $event): void {
        \block_courseaiguide\local\lifecycle::purge_module((int) $event->courseid, (int) $event->contextinstanceid);
        \block_courseaiguide\local\lifecycle::queue_index((int) $event->courseid);
    }

    /**
     * Purge all plugin data after a course is deleted.
     *
     * @param \core\event\course_deleted $event Course deletion event.
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        \block_courseaiguide\local\lifecycle::purge_course((int) $event->objectid);
    }
}
