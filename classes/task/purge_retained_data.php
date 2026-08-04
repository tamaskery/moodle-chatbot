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
namespace block_courseaiguide\task;

use block_courseaiguide\local\config\site_config;

/**
 * Purges expired history and abuse-control windows.
 */
final class purge_retained_data extends \core\task\scheduled_task {
    /**
     * Return the localised task name.
     *
     * @return string Task name.
     */
    public function get_name(): string {
        return get_string('task:purge', 'block_courseaiguide');
    }

    /** Execute task. */
    public function execute(): void {
        global $DB;
        $now = time();
        $days = (new site_config())->retention_days();
        if ($days === 0) {
            $DB->delete_records('block_courseaiguide_msg');
            $DB->delete_records('block_courseaiguide_conv');
        } else {
            $maximumexpiry = $now + ($days * DAYSECS);
            $DB->set_field_select('block_courseaiguide_msg', 'expiresat', $maximumexpiry,
                'expiresat > :maximumexpiry', ['maximumexpiry' => $maximumexpiry]);
            $DB->set_field_select('block_courseaiguide_conv', 'expiresat', $maximumexpiry,
                'expiresat > :maximumexpiry', ['maximumexpiry' => $maximumexpiry]);
        }

        $disabledcourses = $DB->get_fieldset_select('block_courseaiguide_course', 'courseid',
            'historyenabled = :historyenabled', ['historyenabled' => 0]);
        if ($disabledcourses) {
            [$coursesql, $courseparams] = $DB->get_in_or_equal(
                $disabledcourses,
                SQL_PARAMS_NAMED,
                'historydisabled'
            );
            $DB->delete_records_select('block_courseaiguide_msg', "courseid $coursesql", $courseparams);
            $DB->delete_records_select('block_courseaiguide_conv', "courseid $coursesql", $courseparams);
        }

        $DB->delete_records_select('block_courseaiguide_msg', 'expiresat <= :now', ['now' => $now]);
        $expired = $DB->get_fieldset_select('block_courseaiguide_conv', 'id', 'expiresat <= :now', ['now' => $now]);
        if ($expired) {
            [$insql, $params] = $DB->get_in_or_equal($expired, SQL_PARAMS_NAMED, 'expiredconv');
            $DB->delete_records_select('block_courseaiguide_msg', "conversationid $insql", $params);
            $DB->delete_records_select('block_courseaiguide_conv', "id $insql", $params);
        }
        $DB->delete_records_select('block_courseaiguide_rate', 'windowend <= :now', ['now' => $now]);
    }
}
