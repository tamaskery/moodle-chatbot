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

/**
 * Course AI Guide block.
 */
class block_courseaiguide extends block_base {
    /** Initialise the block. */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_courseaiguide');
    }

    /** @return bool */
    public function has_config(): bool {
        return true;
    }

    /** @return bool */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /** @return array */
    public function applicable_formats(): array {
        return [
            'all' => false,
            'course-view' => true,
            'my' => false,
            'site-index' => false,
        ];
    }

    /** @return stdClass */
    public function get_content(): stdClass {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        if (empty($this->page->course->id) || (int) $this->page->course->id === SITEID) {
            return $this->content;
        }

        $courseid = (int) $this->page->course->id;
        $context = context_course::instance($courseid);
        $config = \block_courseaiguide\local\config\course_config::get($courseid);
        $guard = new \block_courseaiguide\local\access\course_guard();
        $canask = $guard->can_ask($courseid);
        $canmanage = has_capability('block/courseaiguide:manage', $context, $USER->id);
        $siteconfig = new \block_courseaiguide\local\config\site_config();
        $historyavailable = $config && $siteconfig->retention_days() > 0 && !empty($config->historyenabled)
            && has_capability('block/courseaiguide:ask', $context, $USER->id);
        $this->content->text = $OUTPUT->render_from_template('block_courseaiguide/block', [
            'courseid' => $courseid,
            'canask' => $canask,
            'canmanage' => $canmanage,
            'status' => $config ? get_string('indexstatus:' . $config->indexstatus, 'block_courseaiguide') :
                get_string('indexstatus:disabled', 'block_courseaiguide'),
            'chaturl' => (new moodle_url('/blocks/courseaiguide/chat.php', ['courseid' => $courseid]))->out(false),
            'reindexurl' => (new moodle_url('/blocks/courseaiguide/reindex.php', [
                'courseid' => $courseid,
            ]))->out(false),
            'reporturl' => (new moodle_url('/blocks/courseaiguide/report.php', ['courseid' => $courseid]))->out(false),
            'historyavailable' => $historyavailable,
            'historyurl' => (new moodle_url('/blocks/courseaiguide/history.php', ['courseid' => $courseid]))->out(false),
        ]);
        if ($canask) {
            $this->page->requires->js_call_amd('block_courseaiguide/chat', 'init', [$courseid]);
        }
        return $this->content;
    }

    /** @return bool */
    public function instance_create(): bool {
        if (!empty($this->page->course->id) && (int) $this->page->course->id !== SITEID) {
            $data = (object) ['enabled' => 0];
            \block_courseaiguide\local\config\course_config::save(
                (int) $this->page->course->id,
                (int) $this->instance->id,
                $data
            );
        }
        return true;
    }

    /**
     * Save configuration into the course-owned table and queue indexing.
     *
     * @param stdClass $data
     * @param bool $nolongerused
     * @return bool
     */
    public function instance_config_save($data, $nolongerused = false): bool {
        $data->instructions = trim(clean_param((string) ($data->instructions ?? ''), PARAM_TEXT));
        $saved = parent::instance_config_save($data, $nolongerused);
        if ($saved && !empty($this->page->course->id)) {
            $record = \block_courseaiguide\local\config\course_config::save(
                (int) $this->page->course->id,
                (int) $this->instance->id,
                $data
            );
            if (!empty($record->enabled)) {
                \block_courseaiguide\local\lifecycle::queue_index((int) $record->courseid);
            }
        }
        return $saved;
    }

    /** @return bool */
    public function instance_delete(): bool {
        global $DB;
        $record = $DB->get_record('block_courseaiguide_course', ['blockinstanceid' => $this->instance->id]);
        if ($record) {
            \block_courseaiguide\local\lifecycle::purge_course((int) $record->courseid);
        }
        return true;
    }
}
