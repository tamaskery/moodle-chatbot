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
namespace block_courseaiguide\local\config;

use block_courseaiguide\local\index\source_registry;

/**
 * Per-course configuration repository and normaliser.
 */
final class course_config {
    /**
     * Get a configuration record.
     *
     * @param int $courseid
     * @return \stdClass|null
     */
    public static function get(int $courseid): ?\stdClass {
        global $DB;
        $record = $DB->get_record('block_courseaiguide_course', ['courseid' => $courseid]);
        return $record ?: null;
    }

    /**
     * Save sanitised instance configuration.
     *
     * @param int $courseid
     * @param int $blockinstanceid
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function save(int $courseid, int $blockinstanceid, \stdClass $data): \stdClass {
        global $DB;

        $enabledtypes = [];
        foreach (source_registry::all_types() as $type => $unused) {
            if (!empty($data->{'source_' . $type})) {
                $enabledtypes[] = $type;
            }
        }
        $instructions = trim(clean_param((string) ($data->instructions ?? ''), PARAM_TEXT));
        $instructions = \core_text::substr($instructions, 0, 2000);
        $sourceareas = json_encode(array_values($enabledtypes), JSON_UNESCAPED_SLASHES);
        $confighash = hash('sha256', json_encode([
            (bool) ($data->enabled ?? false),
            (bool) ($data->participantsenabled ?? false),
            $sourceareas,
            $instructions,
            (bool) ($data->historyenabled ?? false),
        ]));

        $now = time();
        $record = self::get($courseid);
        $configurationchanged = !$record || !hash_equals((string) ($record->confighash ?? ''), $confighash);
        if (!$record) {
            $record = (object) [
                'courseid' => $courseid,
                'blockinstanceid' => $blockinstanceid,
                'enabled' => 0,
                'participantsenabled' => 0,
                'sourceareas' => '[]',
                'instructions' => '',
                'historyenabled' => 0,
                'indexstatus' => 'disabled',
                'indexgeneration' => 0,
                'confighash' => '',
                'contenthash' => null,
                'indexerror' => null,
                'timeindexed' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
        }
        $record->blockinstanceid = $blockinstanceid;
        $record->enabled = empty($data->enabled) ? 0 : 1;
        $record->participantsenabled = empty($data->participantsenabled) ? 0 : 1;
        $record->sourceareas = $sourceareas;
        $record->instructions = $instructions;
        $record->historyenabled = empty($data->historyenabled) ? 0 : 1;
        $record->confighash = $confighash;
        $record->timemodified = $now;
        if (!$record->enabled) {
            $record->indexstatus = 'disabled';
            $record->indexerror = null;
        } else if ($configurationchanged) {
            $record->indexstatus = 'pending';
            $record->indexerror = null;
        }

        if (empty($record->id)) {
            $record->id = $DB->insert_record('block_courseaiguide_course', $record);
        } else {
            $DB->update_record('block_courseaiguide_course', $record);
        }
        return $record;
    }

    /**
     * Get enabled type names safely.
     *
     * @param \stdClass $record
     * @return array
     */
    public static function enabled_types(\stdClass $record): array {
        $decoded = json_decode((string) $record->sourceareas, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_intersect(array_keys(source_registry::all_types()), $decoded));
    }
}
