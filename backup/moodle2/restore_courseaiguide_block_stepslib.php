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
/**
 * Restore safe course configuration only.
 */
class restore_courseaiguide_block_structure_step extends restore_structure_step {
    /**
     * Define the block restore paths.
     *
     * @return array The restore path elements.
     */
    protected function define_structure(): array {
        return [new restore_path_element('courseaiguide', '/block/courseaiguide')];
    }

    /**
     * Restore one Course AI Assistant configuration record.
     *
     * @param array|stdClass $data Restored record data.
     */
    public function process_courseaiguide($data): void {
        $data = (object) $data;
        $config = (object) [
            'enabled' => empty($data->enabled) ? 0 : 1,
            'participantsenabled' => 0,
            'instructions' => \core_text::substr(clean_param((string) ($data->instructions ?? ''), PARAM_TEXT), 0, 2000),
            'historyenabled' => empty($data->historyenabled) ? 0 : 1,
        ];
        $types = json_decode((string) ($data->sourceareas ?? '[]'), true);
        if (is_array($types)) {
            foreach (\block_courseaiguide\local\index\source_registry::all_types() as $type => $unused) {
                $config->{'source_' . $type} = in_array($type, $types, true) ? 1 : 0;
            }
        }
        \block_courseaiguide\local\config\course_config::save(
            (int) $this->get_courseid(),
            (int) $this->get_task()->get_blockid(),
            $config
        );
    }
}
