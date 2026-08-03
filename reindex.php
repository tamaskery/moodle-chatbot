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
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('block/courseaiguide:manage', $context);
$returnurl = new moodle_url('/course/view.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/courseaiguide/reindex.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('reindex', 'block_courseaiguide'));
$PAGE->set_heading(format_string($course->fullname));

if (data_submitted()) {
    require_sesskey();
    \block_courseaiguide\local\lifecycle::queue_index($courseid);
    redirect($returnurl, get_string('reindexqueued', 'block_courseaiguide'));
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('reindexconfirm', 'block_courseaiguide'),
    new single_button($PAGE->url, get_string('reindex', 'block_courseaiguide'), 'post'),
    $returnurl
);
echo $OUTPUT->footer();
