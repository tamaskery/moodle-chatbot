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
/**
 * Per-instance configuration form.
 */
class block_courseaiguide_edit_form extends block_edit_form {
    /**
     * Add course settings.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'configheader', get_string('courseconfig', 'block_courseaiguide'));
        $mform->addElement('advcheckbox', 'config_enabled', get_string('enabled', 'block_courseaiguide'));
        $mform->setDefault('config_enabled', 0);
        $mform->addElement(
            'advcheckbox',
            'config_participantsenabled',
            get_string('participantsenabled', 'block_courseaiguide')
        );
        $mform->setDefault('config_participantsenabled', 0);

        foreach (\block_courseaiguide\local\index\source_registry::all_types() as $type => $areaid) {
            $mform->addElement(
                'advcheckbox',
                'config_source_' . $type,
                get_string('source:' . $type, 'block_courseaiguide')
            );
            $mform->setDefault('config_source_' . $type, 1);
        }

        $mform->addElement(
            'textarea',
            'config_instructions',
            get_string('courseinstructions', 'block_courseaiguide'),
            ['rows' => 5, 'cols' => 60]
        );
        $mform->setType('config_instructions', PARAM_TEXT);
        $mform->addRule('config_instructions', get_string('maximumchars', '', 2000), 'maxlength', 2000, 'client');
        $mform->addElement('advcheckbox', 'config_historyenabled', get_string('historyenabled', 'block_courseaiguide'));
        $mform->setDefault('config_historyenabled', 0);
        $mform->addElement('static', 'readinessnote', '', get_string('readinessnote', 'block_courseaiguide'));
    }
}
