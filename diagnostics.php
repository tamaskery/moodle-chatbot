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
 * Manager-only incident diagnostic controls and viewer.
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
require_capability('block/courseaiguide:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/courseaiguide/diagnostics.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('diagnosticsheading', 'block_courseaiguide'));
$PAGE->set_heading(format_string($course->fullname));

$siteconfig = new \block_courseaiguide\local\config\site_config();
$courseconfig = \block_courseaiguide\local\config\course_config::get($courseid);
if (!$courseconfig) {
    throw new moodle_exception('error:notenabled', 'block_courseaiguide');
}
$service = new \block_courseaiguide\local\diagnostic\diagnostic_service();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    if ($action === 'arm') {
        if ($siteconfig->diagnostic_retention_hours() <= 0) {
            throw new moodle_exception('error:diagnosticsdisabled', 'block_courseaiguide');
        }
        \block_courseaiguide\local\config\course_config::set_diagnostic_until($courseid, time() + HOURSECS);
        redirect($PAGE->url, get_string('diagnosticsarmed', 'block_courseaiguide'));
    } else if ($action === 'disarm') {
        \block_courseaiguide\local\config\course_config::set_diagnostic_until($courseid, null);
        redirect($PAGE->url, get_string('diagnosticsdisarmed', 'block_courseaiguide'));
    } else if ($action === 'deleteall') {
        $service->delete_course($courseid);
        redirect($PAGE->url, get_string('diagnosticsdeleted', 'block_courseaiguide'));
    } else if ($action === 'delete') {
        $service->delete_record($courseid, required_param('recordid', PARAM_INT));
        redirect($PAGE->url, get_string('diagnosticdeleted', 'block_courseaiguide'));
    }
    throw new invalid_parameter_exception('Unknown diagnostic action.');
}

$courseconfig = \block_courseaiguide\local\config\course_config::get($courseid);
$active = \block_courseaiguide\local\config\course_config::diagnostic_active($courseconfig);
$records = $service->list_course($courseid);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('diagnosticsheading', 'block_courseaiguide'));
echo html_writer::tag('p', get_string('diagnosticsexplanation', 'block_courseaiguide'));

if ($siteconfig->diagnostic_retention_hours() <= 0) {
    echo $OUTPUT->notification(get_string('diagnosticsdisabled', 'block_courseaiguide'), 'info');
} else {
    $status = $active
        ? get_string('diagnosticsactive', 'block_courseaiguide', userdate((int) $courseconfig->diagnosticuntil))
        : get_string('diagnosticsinactive', 'block_courseaiguide');
    echo $OUTPUT->notification($status, $active ? 'warning' : 'info');
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url, 'class' => 'mb-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => $active ? 'disarm' : 'arm',
    ]);
    echo html_writer::tag(
        'button',
        get_string($active ? 'diagnosticsdisarm' : 'diagnosticsarm', 'block_courseaiguide'),
        ['type' => 'submit', 'class' => $active ? 'btn btn-secondary' : 'btn btn-warning']
    );
    echo html_writer::end_tag('form');
}

echo $OUTPUT->heading(get_string('diagnosticcaptures', 'block_courseaiguide'), 3);
if (!$records) {
    echo html_writer::tag('p', get_string('diagnosticsnone', 'block_courseaiguide'));
} else {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url, 'class' => 'mb-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'deleteall']);
    echo html_writer::tag('button', get_string('diagnosticsdeleteall', 'block_courseaiguide'), [
        'type' => 'submit',
        'class' => 'btn btn-danger',
    ]);
    echo html_writer::end_tag('form');

    foreach ($records as $record) {
        $user = core_user::get_user($record->userid);
        $username = $user ? fullname($user) : get_string('diagnosticdeleteduser', 'block_courseaiguide');
        $summary = get_string('diagnosticsummary', 'block_courseaiguide', (object) [
            'time' => userdate($record->timecreated),
            'user' => $username,
            'requestid' => $record->requestid,
            'mode' => $record->mode,
        ]);
        echo html_writer::start_tag('details', ['class' => 'card card-body mb-3']);
        echo html_writer::tag('summary', s($summary), ['class' => 'font-weight-bold']);
        $diagnosticfields = [
            'diagnosticquestion' => $record->question,
            'diagnosticanswer' => $record->answer,
            'diagnosticfacts' => $record->factsjson,
            'diagnosticsources' => $record->sourcesjson,
            'diagnosticreferences' => $record->referencejson,
            'diagnosticguidance' => $record->guidance,
        ];
        foreach ($diagnosticfields as $label => $value) {
            echo $OUTPUT->heading(get_string($label, 'block_courseaiguide'), 5);
            echo html_writer::tag('pre', s((string) $value), ['class' => 'text-wrap']);
        }
        echo html_writer::tag('p', s(get_string('diagnosticmetadata', 'block_courseaiguide', (object) [
            'model' => $record->model,
            'host' => $record->providerhost,
            'diagnostic' => $record->diagnostic ?: get_string('none'),
            'version' => $record->pluginversion,
            'expires' => userdate($record->expiresat),
        ])));
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'delete']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'recordid', 'value' => $record->id]);
        echo html_writer::tag('button', get_string('delete'), ['type' => 'submit', 'class' => 'btn btn-danger btn-sm']);
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('details');
    }
}

echo $OUTPUT->footer();
