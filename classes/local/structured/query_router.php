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
namespace block_courseaiguide\local\structured;

/**
 * Conservative deterministic router for authoritative Moodle facts.
 */
final class query_router {
    /**
     * Maximum number of authoritative dates returned by a broad question.
     */
    private const MAX_DATE_FACTS = 20;

    /**
     * Language that asks about an activity schedule or a possible change to it.
     */
    private const DATE_INTENT_PATTERN =
        '/\b(when|deadlines?|due|opens?|opening|close|closes|closing|schedules?|reschedul(?:e|ed|ing)|'
        . 'extensions?|extended|postponed?|cut.?offs?)\b|submission\s+(?:window|date)/u';

    /**
     * Date intent that should prefer deadline-like dates over opening dates.
     */
    private const DEADLINE_INTENT_PATTERN =
        '/\b(deadlines?|due|close|closes|closing|schedules?|reschedul(?:e|ed|ing)|extensions?|extended|'
        . 'postponed?|cut.?offs?)\b|submission\s+(?:window|date)/u';

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
        if (
            preg_match('/\b(course).{0,30}(complete|completed|completion)\b/u', $normalised)
                || preg_match('/\b(complete|completed|completion).{0,30}(course)\b/u', $normalised)
        ) {
            return $this->course_completion($courseid, $userid);
        }
        if (
            preg_match('/\b(what|which).{0,30}(next|complete next|do next)\b/u', $normalised)
                || strpos($normalised, 'what next') !== false
        ) {
            return $this->next_activity($courseid, $userid);
        }
        if (preg_match(self::DATE_INTENT_PATTERN, $normalised)) {
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
        $eligible = array_values(array_filter($modinfo->get_cms(), static function ($cm): bool {
            return $cm->visible && $cm->uservisible && !empty($cm->url);
        }));
        $matches = [];
        foreach ($eligible as $cm) {
            $name = \core_text::strtolower($cm->name);
            if ($name !== '' && strpos($question, $name) !== false) {
                $matches[] = $cm;
            }
        }
        if (!$matches) {
            $typematches = [
                'assign' => '/\bassignments?\b/u',
                'quiz' => '/\b(quiz|quizzes)\b/u',
                'scorm' => '/\bscorm\b/u',
            ];
            foreach ($typematches as $modname => $pattern) {
                if (!preg_match($pattern, $question)) {
                    continue;
                }
                $matches = array_values(array_filter($eligible, static function ($cm) use ($modname): bool {
                    return $cm->modname === $modname;
                }));
                break;
            }
        }
        if (!$matches && preg_match(self::DEADLINE_INTENT_PATTERN, $question)) {
            $matches = $eligible;
        }
        if (!$matches) {
            return $this->not_found();
        }

        if (count($matches) === 1) {
            return $this->single_activity_dates(reset($matches), $userid);
        }
        return $this->multiple_activity_dates($matches, $userid, $question);
    }

    /**
     * Build the existing detailed answer for one unambiguous activity.
     *
     * @param \cm_info $cm
     * @param int $userid
     * @return array
     */
    private function single_activity_dates(\cm_info $cm, int $userid): array {
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
            $label = rtrim(clean_param((string) $date['label'], PARAM_TEXT), " :\t\n\r\0\x0B");
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
     * Build a bounded chronological answer across visible activities.
     *
     * @param array $cms
     * @param int $userid
     * @param string $question
     * @return array
     */
    private function multiple_activity_dates(array $cms, int $userid, string $question): array {
        $deadlineonly = (bool) preg_match(self::DEADLINE_INTENT_PATTERN, $question)
            && !preg_match('/\b(opens?|opening)\b/u', $question);
        $items = [];
        foreach ($cms as $cm) {
            $dates = \core\activity_dates::get_dates_for_module($cm, $userid);
            if (!$dates) {
                continue;
            }
            foreach ($dates as $date) {
                if (empty($date['timestamp'])) {
                    continue;
                }
                $label = rtrim(clean_param((string) $date['label'], PARAM_TEXT), " :\t\n\r\0\x0B");
                if ($deadlineonly && !preg_match('/\b(due|close|closes|closing|deadline|cut.?off|until|end)\b/ui', $label)) {
                    continue;
                }
                $items[] = [
                    'timestamp' => (int) $date['timestamp'],
                    'cm' => $cm,
                    'label' => $label,
                ];
            }
        }
        usort($items, static function (array $a, array $b): int {
            return $a['timestamp'] <=> $b['timestamp'] ?: $a['cm']->id <=> $b['cm']->id;
        });
        $items = array_slice($items, 0, self::MAX_DATE_FACTS);
        if (!$items) {
            return $this->not_found();
        }

        $facts = [];
        $displaydates = [];
        $sources = [];
        foreach ($items as $item) {
            $cm = $item['cm'];
            $display = userdate($item['timestamp']);
            $factlabel = get_string('activitydatelabel', 'block_courseaiguide', (object) [
                'activity' => $cm->name,
                'label' => $item['label'],
            ]);
            $url = $cm->url->out(false);
            $displaydates[] = $factlabel . ': ' . $display;
            $facts[] = [
                'type' => 'activitydate',
                'label' => $factlabel,
                'value' => $display,
                'url' => $url,
            ];
            $sources[$cm->id] = [
                'id' => 0,
                'title' => $cm->name,
                'type' => $cm->modname,
                'url' => $url,
            ];
        }
        return [
            'mode' => 'structured',
            'answer' => get_string('deadlinesanswer', 'block_courseaiguide', implode('; ', $displaydates)),
            'facts' => $facts,
            'sources' => array_values($sources),
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

    /**
     * Return the deterministic not-found response.
     *
     * @return array Structured response payload.
     */
    private function not_found(): array {
        return [
            'mode' => 'notfound',
            'answer' => get_string('notfound', 'block_courseaiguide'),
            'facts' => [],
            'sources' => [],
        ];
    }
}
