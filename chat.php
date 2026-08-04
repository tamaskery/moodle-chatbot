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
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/courseaiguide/chat.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('chatheading', 'block_courseaiguide'));
$PAGE->set_heading(format_string($course->fullname));

$config = (new \block_courseaiguide\local\access\course_guard())->require_ask($courseid, (int) $USER->id);
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $question = required_param('question', PARAM_TEXT);
    $savehistory = optional_param('savehistory', 0, PARAM_BOOL);
    $conversationid = optional_param('conversationid', '', PARAM_ALPHANUMEXT);
    $result = (new \block_courseaiguide\local\chat\orchestrator())->ask(
        $courseid,
        (int) $USER->id,
        $question,
        (bool) $savehistory,
        $conversationid
    );
}

$siteconfig = new \block_courseaiguide\local\config\site_config();
$historyavailable = $siteconfig->retention_days() > 0 && !empty($config->historyenabled);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('chatheading', 'block_courseaiguide'));
echo html_writer::div(s($siteconfig->disclaimer()), 'alert alert-info');
echo html_writer::tag('p', get_string('examples', 'block_courseaiguide'));

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('question', 'block_courseaiguide'), ['for' => 'courseaiguide-question']);
echo html_writer::tag('textarea', '', [
    'id' => 'courseaiguide-question',
    'name' => 'question',
    'class' => 'form-control mb-3',
    'rows' => 5,
    'maxlength' => 2000,
    'required' => 'required',
]);
if ($historyavailable) {
    echo html_writer::start_div('form-check mb-3');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'savehistory',
        'value' => 1,
        'id' => 'courseaiguide-save',
        'class' => 'form-check-input',
    ]);
    echo html_writer::tag('label', get_string('savehistory', 'block_courseaiguide'), [
        'for' => 'courseaiguide-save',
        'class' => 'form-check-label',
    ]);
    echo html_writer::end_div();
}
echo html_writer::tag('button', get_string('ask', 'block_courseaiguide'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

if ($result) {
    if ($result['facts']) {
        echo $OUTPUT->heading(get_string('authoritativefacts', 'block_courseaiguide'), 3);
        echo html_writer::start_tag('ul');
        foreach ($result['facts'] as $fact) {
            $value = s($fact['label'] . ': ' . $fact['value']);
            if (!empty($fact['url'])) {
                $value .= ' ' . html_writer::link(new moodle_url($fact['url']), s($fact['label']));
            }
            echo html_writer::tag('li', $value);
        }
        echo html_writer::end_tag('ul');
    }
    if ($result['mode'] !== 'structured') {
        echo $OUTPUT->heading(get_string('generatedexplanation', 'block_courseaiguide'), 3);
        echo html_writer::tag('p', s($result['answer']));
    }
    if ($result['sources']) {
        echo $OUTPUT->heading(get_string('sources', 'block_courseaiguide'), 3);
        echo html_writer::start_tag('ul');
        foreach ($result['sources'] as $source) {
            if (!empty($source['url'])) {
                echo html_writer::tag('li', html_writer::link(new moodle_url($source['url']), s($source['title'])));
            }
        }
        echo html_writer::end_tag('ul');
    }
}

echo $OUTPUT->footer();
