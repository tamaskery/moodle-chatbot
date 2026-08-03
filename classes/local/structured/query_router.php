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
namespace block_courseaiguide\local\structured;

defined('MOODLE_INTERNAL') || die();

/**
 * Conservative deterministic router for authoritative Moodle facts.
 */
final class query_router {
    /**
     * Return a structured answer or null when textual retrieval should handle the question.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $question
     * @return array|null
     */
    public function answer(int $courseid, int $userid, string $question): ?array {
        $normalised = \core_text::strtolower(clean_param($question, PARAM_TEXT));
        if (preg_match('/\b(course).{0,30}(complete|completed|completion)\b/u', $normalised)
                || preg_match('/\b(complete|completed|completion).{0,30}(course)\b/u', $normalised)) {
            return $this->course_completion($courseid, $userid);
        }
        if (preg_match('/\b(what|which).{0,30}(next|complete next|do next)\b/u', $normalised)
                || strpos($normalised, 'what next') !== false) {
            return $this->next_activity($courseid, $userid);
        }
        if (preg_match('/\b(when|deadline|due|open|close|closes)\b/u', $normalised)) {
            return $this->dates($courseid, $userid, $normalised);
        }
        return null;
    }

    /**
     * Return current-user course completion without grade data.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    private function course_completion(int $courseid, int $userid): array {
        $course = get_course($courseid);
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return $this->not_found();
        }
        $complete = $completion->is_course_complete($userid);
        $value = get_string($complete ? 'coursecomplete' : 'coursenotcomplete', 'block_courseaiguide');
        $url = (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        return [
            'mode' => 'structured',
            'answer' => $value,
            'facts' => [[
                'type' => 'coursecompletion',
                'label' => get_string('coursecompletion', 'block_courseaiguide'),
                'value' => $value,
                'url' => $url,
            ]],
            'sources' => [[
                'id' => 0,
                'title' => format_string($course->fullname),
                'type' => 'course',
                'url' => $url,
            ]],
        ];
    }

    /**
     * Build user-specific activity date facts.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $question
     * @return array
     */
    private function dates(int $courseid, int $userid, string $question): array {
        $modinfo = get_fast_modinfo($courseid, $userid);
        $matches = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->visible || !$cm->uservisible || !$cm->url) {
                continue;
            }
            $name = \core_text::strtolower($cm->name);
            if ($name !== '' && strpos($question, $name) !== false) {
                $matches[] = $cm;
            }
        }
        if (!$matches) {
            foreach (['quiz', 'assign'] as $modname) {
                $keyword = $modname === 'assign' ? 'assignment' : 'quiz';
                if (strpos($question, $keyword) === false) {
                    continue;
                }
                $typed = array_values(array_filter($modinfo->get_instances_of($modname), static function($cm): bool {
                    return $cm->visible && $cm->uservisible && !empty($cm->url);
                }));
                if (count($typed) === 1) {
                    $matches = $typed;
                }
            }
        }
        if (count($matches) !== 1) {
            return $this->not_found();
        }
        $cm = reset($matches);
        $dates = \core\activity_dates::get_dates_for_module($cm, $userid);
        if (!$dates) {
            return $this->not_found();
        }
        $facts = [];
        $displaydates = [];
        foreach ($dates as $date) {
            if (empty($date['timestamp'])) {
                continue;
            }
            $display = userdate((int) $date['timestamp']);
            $label = clean_param((string) $date['label'], PARAM_TEXT);
            $displaydates[] = $label . ': ' . $display;
            $facts[] = [
                'type' => 'activitydate',
                'label' => $label,
                'value' => $display,
                'url' => $cm->url->out(false),
            ];
        }
        if (!$facts) {
            return $this->not_found();
        }
        return [
            'mode' => 'structured',
            'answer' => get_string('deadlineanswer', 'block_courseaiguide', (object) [
                'activity' => $cm->name,
                'dates' => implode('; ', $displaydates),
            ]),
            'facts' => $facts,
            'sources' => [[
                'id' => 0,
                'title' => $cm->name,
                'type' => $cm->modname,
                'url' => $cm->url->out(false),
            ]],
        ];
    }

    /**
     * Select the first currently eligible incomplete activity in course order.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    private function next_activity(int $courseid, int $userid): array {
        $modinfo = get_fast_modinfo($courseid, $userid);
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->visible || !$cm->uservisible || !$cm->url) {
                continue;
            }
            try {
                $details = \core_completion\cm_completion_details::get_instance($cm, $userid, false);
                if (!$details->has_completion() || !$details->is_tracked_user() || $details->is_overall_complete()) {
                    continue;
                }
            } catch (\Throwable $e) {
                continue;
            }
            return [
                'mode' => 'structured',
                'answer' => get_string('nextanswer', 'block_courseaiguide', $cm->name),
                'facts' => [[
                    'type' => 'nextactivity',
                    'label' => get_string('modulename', 'mod_' . $cm->modname),
                    'value' => $cm->name,
                    'url' => $cm->url->out(false),
                ]],
                'sources' => [[
                    'id' => 0,
                    'title' => $cm->name,
                    'type' => $cm->modname,
                    'url' => $cm->url->out(false),
                ]],
            ];
        }
        return $this->not_found();
    }

    /** @return array */
    private function not_found(): array {
        return [
            'mode' => 'notfound',
            'answer' => get_string('notfound', 'block_courseaiguide'),
            'facts' => [],
            'sources' => [],
        ];
    }
}
