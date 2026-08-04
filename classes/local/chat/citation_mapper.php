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
namespace block_courseaiguide\local\chat;

use block_courseaiguide\local\retrieval\permission_filter;

/**
 * Maps model-selected IDs back to currently authorised server sources.
 */
final class citation_mapper {
    /**
     * Re-authorise and map known source ids.
     *
     * @param int $courseid
     * @param int $userid
     * @param array $allowedids
     * @param array $selectedids
     * @return array
     */
    public function map(int $courseid, int $userid, array $allowedids, array $selectedids): array {
        global $DB;
        $allowed = array_fill_keys(array_map('intval', $allowedids), true);
        $sources = [];
        foreach (array_values(array_unique(array_map('intval', $selectedids))) as $sourceid) {
            if (!$sourceid || !isset($allowed[$sourceid])) {
                continue;
            }
            $record = $DB->get_record('block_courseaiguide_source', [
                'id' => $sourceid,
                'courseid' => $courseid,
            ]);
            if (!$record) {
                continue;
            }
            $record->sourceid = $record->id;
            $source = (new permission_filter())->authorise($record, $courseid, $userid);
            if ($source && $source['url'] !== '') {
                $sources[] = $source;
            }
        }
        return $sources;
    }
}
