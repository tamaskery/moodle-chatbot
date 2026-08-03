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
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/backup_courseaiguide_block_stepslib.php');

/** Backup task for the Course AI Guide block. */
class backup_courseaiguide_block_task extends backup_block_task {
    /** Define settings. */
    protected function define_my_settings(): void {
    }

    /** Define steps. */
    protected function define_my_steps(): void {
        $this->add_step(new backup_courseaiguide_block_structure_step('courseaiguide_structure', 'courseaiguide.xml'));
    }

    /** @return array */
    public function get_fileareas(): array {
        return [];
    }

    /** @param string $content @return string */
    public static function encode_content_links($content): string {
        return $content;
    }
}
