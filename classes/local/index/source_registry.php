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
namespace block_courseaiguide\local\index;

defined('MOODLE_INTERNAL') || die();

/**
 * Explicit whitelist of Moodle Search areas used by the plugin.
 */
final class source_registry {
    /**
     * Return configured source type to Search-area id mappings.
     *
     * @return array<string, string>
     */
    public static function all_types(): array {
        return [
            'course' => 'core_course-course',
            'section' => 'core_course-section',
            'page' => 'mod_page-activity',
            'book' => 'mod_book-activity',
            'bookchapter' => 'mod_book-chapter',
            'label' => 'mod_label-activity',
            'assignment' => 'mod_assign-activity',
            'quizdescription' => 'mod_quiz-activity',
            'url' => 'mod_url-activity',
        ];
    }

    /**
     * Return enabled and known area ids.
     *
     * @param array $enabledtypes
     * @return array<string, string>
     */
    public static function enabled_areas(array $enabledtypes): array {
        return array_intersect_key(self::all_types(), array_fill_keys($enabledtypes, true));
    }

    /**
     * Resolve the type for a whitelisted area.
     *
     * @param string $areaid
     * @return string|null
     */
    public static function type_for_area(string $areaid): ?string {
        $type = array_search($areaid, self::all_types(), true);
        return $type === false ? null : $type;
    }
}
