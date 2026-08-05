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

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('block/courseaiguide:viewreports', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/courseaiguide/report.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('reportheading', 'block_courseaiguide'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportheading', 'block_courseaiguide'));
$siteconfig = new \block_courseaiguide\local\config\site_config();
if (!$siteconfig->statistics_enabled()) {
    echo $OUTPUT->notification(get_string('reportdisabled', 'block_courseaiguide'), 'info');
} else {
    $records = $DB->get_records('block_courseaiguide_usage', ['courseid' => $courseid], 'daystart DESC');
    $table = new html_table();
    $table->head = [
        get_string('reportday', 'block_courseaiguide'),
        get_string('reportrequests', 'block_courseaiguide'),
        get_string('reporterrors', 'block_courseaiguide'),
        get_string('reportnotfound', 'block_courseaiguide'),
        get_string('reportlatency', 'block_courseaiguide'),
    ];
    foreach ($records as $record) {
        $average = $record->requests ? round($record->latencytotal / $record->requests) : 0;
        $table->data[] = [
            userdate($record->daystart, get_string('strftimedatefullshort', 'langconfig')),
            (int) $record->requests,
            (int) $record->errors,
            (int) $record->notfound,
            $average,
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
