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
defined('MOODLE_INTERNAL') || die();

/** Safe course-configuration backup structure. */
class backup_courseaiguide_block_structure_step extends backup_block_structure_step {
    /** @return backup_nested_element */
    protected function define_structure(): backup_nested_element {
        $guide = new backup_nested_element('courseaiguide', ['id'], [
            'enabled', 'sourceareas', 'instructions', 'historyenabled',
        ]);
        $guide->set_source_table('block_courseaiguide_course', [
            'blockinstanceid' => backup::VAR_BLOCKID,
        ]);
        return $this->prepare_block_structure($guide);
    }
}
