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

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$token = optional_param('conversationid', '', PARAM_ALPHANUMEXT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$course = get_course($courseid);
require_login($course);
$context = (new \block_courseaiguide\local\access\course_guard())->require_participant($courseid, (int) $USER->id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/courseaiguide/history.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('myhistory', 'block_courseaiguide'));
$PAGE->set_heading(format_string($course->fullname));
$service = new \block_courseaiguide\local\history\history_service();
if (data_submitted() && $delete) {
    require_sesskey();
    $service->delete_owned($courseid, (int) $USER->id, $token);
    redirect($PAGE->url, get_string('historydeleted', 'block_courseaiguide'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('myhistory', 'block_courseaiguide'));
if ($token !== '') {
    $messages = $service->get_owned($courseid, (int) $USER->id, $token);
    if (!$messages) {
        echo $OUTPUT->notification(get_string('nohistory', 'block_courseaiguide'), 'info');
    } else {
        foreach ($messages as $message) {
            $role = $message->role === 'user' ? get_string('question', 'block_courseaiguide') :
                get_string('generatedexplanation', 'block_courseaiguide');
            echo $OUTPUT->heading($role, 3);
            echo html_writer::tag('p', s($message->content));
        }
        $deleteurl = new moodle_url('/blocks/courseaiguide/history.php', [
            'courseid' => $courseid,
            'conversationid' => $token,
            'delete' => 1,
        ]);
        echo $OUTPUT->single_button(
            $deleteurl,
            get_string('deleteconversation', 'block_courseaiguide'),
            'post',
            ['class' => 'btn-danger']
        );
    }
} else {
    $conversations = $service->list_owned($courseid, (int) $USER->id);
    if (!$conversations) {
        echo $OUTPUT->notification(get_string('nohistory', 'block_courseaiguide'), 'info');
    } else {
        echo html_writer::start_tag('ul');
        foreach ($conversations as $conversation) {
            $url = new moodle_url('/blocks/courseaiguide/history.php', [
                'courseid' => $courseid,
                'conversationid' => $conversation->publictoken,
            ]);
            echo html_writer::tag('li', html_writer::link($url, userdate($conversation->timemodified)));
        }
        echo html_writer::end_tag('ul');
    }
}
echo $OUTPUT->footer();
