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
 * Administrator-only saved provider connection test.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$url = new moodle_url('/blocks/courseaiguide/test_connection.php');
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'blocksettingcourseaiguide']);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('connectiontestheading', 'block_courseaiguide'));
$PAGE->set_heading(get_string('pluginname', 'block_courseaiguide'));
$PAGE->set_pagelayout('admin');

$success = null;
$failure = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if (required_param('action', PARAM_ALPHA) !== 'test') {
        throw new invalid_parameter_exception('Unknown connection-test action.');
    }
    try {
        $success = (new \block_courseaiguide\local\provider\connection_tester())->test();
    } catch (\block_courseaiguide\local\provider\provider_exception $e) {
        $failure = get_string('connectiontestfailed', 'block_courseaiguide', $e->diagnostic());
    } catch (\Throwable $e) {
        $failure = get_string('connectiontestunexpected', 'block_courseaiguide');
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectiontestheading', 'block_courseaiguide'));
echo html_writer::tag('p', get_string('connectiontestintro', 'block_courseaiguide'));
if ($success !== null) {
    echo $OUTPUT->notification(
        get_string('connectiontestsuccess', 'block_courseaiguide', (object) $success),
        'success'
    );
} else if ($failure !== '') {
    echo $OUTPUT->notification($failure, 'error');
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url, 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'test']);
echo html_writer::tag('button', get_string('connectiontestrun', 'block_courseaiguide'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
echo ' ';
echo html_writer::link($settingsurl, get_string('connectiontestback', 'block_courseaiguide'), [
    'class' => 'btn btn-secondary',
]);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
