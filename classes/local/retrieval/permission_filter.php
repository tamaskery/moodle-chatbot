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
namespace block_courseaiguide\local\retrieval;

use block_courseaiguide\local\config\course_config;
use block_courseaiguide\local\index\source_registry;

/**
 * Revalidates current user access for one indexed source.
 */
final class permission_filter {
    /**
     * Return a safe source record or null.
     *
     * @param \stdClass $candidate
     * @param int $courseid
     * @param int $userid
     * @return array|null
     */
    public function authorise(\stdClass $candidate, int $courseid, int $userid): ?array {
        global $CFG, $USER;

        if ((int) $USER->id !== $userid || (int) $candidate->courseid !== $courseid) {
            return null;
        }
        $config = course_config::get($courseid);
        if (
            !$config || (string) $config->indexstatus !== 'ready'
                || (int) $candidate->generation !== (int) $config->indexgeneration
        ) {
            return null;
        }
        if (source_registry::type_for_area((string) $candidate->searcharea) !== (string) $candidate->sourcetype) {
            return null;
        }
        $sourcecontext = \context::instance_by_id((int) $candidate->contextid, IGNORE_MISSING);
        if (!$sourcecontext || !is_descendant_of_course($sourcecontext, $courseid)) {
            return null;
        }

        try {
            $area = \core_search\manager::get_search_area((string) $candidate->searcharea);
            if (!$area || !$area->check_access((int) $candidate->sourceitem)) {
                return null;
            }
            $modinfo = get_fast_modinfo($courseid, $userid);
            if (!empty($candidate->cmid)) {
                $cm = $modinfo->get_cm((int) $candidate->cmid);
                if (!$cm->visible || !$cm->uservisible) {
                    return null;
                }
                $section = $modinfo->get_section_info($cm->sectionnum);
                if (!$section || !$section->visible || !$section->uservisible) {
                    return null;
                }
            } else if (!empty($candidate->sectionid)) {
                $matched = false;
                foreach ($modinfo->get_section_info_all() as $section) {
                    if ((int) $section->id === (int) $candidate->sectionid) {
                        $matched = true;
                        if (!$section->visible || !$section->uservisible) {
                            return null;
                        }
                        break;
                    }
                }
                if (!$matched) {
                    return null;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        $url = trim((string) $candidate->url);
        if ($url !== '' && strpos($url, $CFG->wwwroot . '/') !== 0) {
            $url = '';
        }
        return [
            'id' => (int) $candidate->sourceid,
            'title' => clean_param((string) $candidate->title, PARAM_TEXT),
            'type' => clean_param((string) $candidate->sourcetype, PARAM_ALPHANUMEXT),
            'url' => $url,
        ];
    }
}

/**
 * Determine whether a context belongs to a course without trusting candidate metadata.
 *
 * @param \context $context
 * @param int $courseid
 * @return bool
 */
function is_descendant_of_course(\context $context, int $courseid): bool {
    if ($context->contextlevel === CONTEXT_COURSE) {
        return (int) $context->instanceid === $courseid;
    }
    if ($context->contextlevel === CONTEXT_MODULE) {
        $coursecontext = $context->get_course_context(false);
        return $coursecontext && (int) $coursecontext->instanceid === $courseid;
    }
    return false;
}
