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
namespace block_courseaiguide\local\access;

use block_courseaiguide\local\config\course_config;
use block_courseaiguide\local\config\site_config;

defined('MOODLE_INTERNAL') || die();

/**
 * Central fail-closed course request guard.
 */
final class course_guard {
    /**
     * Require access to ask in a course.
     *
     * @param int $courseid
     * @param int|null $userid
     * @return \stdClass Course configuration.
     */
    public function require_ask(int $courseid, ?int $userid = null): \stdClass {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;
        $context = $this->require_participant($courseid, $userid);
        $ismanaging = has_capability('block/courseaiguide:manage', $context, $userid);

        $config = course_config::get($courseid);
        if (!$config || empty($config->enabled)) {
            throw new \moodle_exception('error:notenabled', 'block_courseaiguide');
        }
        if (!$DB->record_exists('block_instances', [
            'id' => $config->blockinstanceid,
            'blockname' => 'courseaiguide',
            'parentcontextid' => $context->id,
        ])) {
            throw new \moodle_exception('error:notenabled', 'block_courseaiguide');
        }
        if (!$ismanaging && empty($config->participantsenabled)) {
            throw new \moodle_exception('error:participantsdisabled', 'block_courseaiguide');
        }
        $siteconfig = new site_config();
        if (!$siteconfig->provider_ready()) {
            throw new \moodle_exception('error:providernotready', 'block_courseaiguide');
        }
        if ((string) $config->indexstatus !== 'ready') {
            throw new \moodle_exception('error:indexnotready', 'block_courseaiguide');
        }
        return $config;
    }

    /**
     * Require current enrolment-or-manager access without requiring an active AI provider/index.
     * Used for participant-owned privacy controls.
     *
     * @param int $courseid
     * @param int|null $userid
     * @return \context_course
     */
    public function require_participant(int $courseid, ?int $userid = null): \context_course {
        global $USER;
        $userid = $userid ?? (int) $USER->id;
        $context = \context_course::instance($courseid, MUST_EXIST);
        self::validate_current_user($userid);
        require_capability('block/courseaiguide:ask', $context, $userid);
        $ismanaging = has_capability('block/courseaiguide:manage', $context, $userid);
        if (!$ismanaging && (isguestuser($userid) || !is_enrolled($context, $userid, '', true))) {
            throw new \required_capability_exception($context, 'block/courseaiguide:ask', 'nopermissions', '');
        }
        return $context;
    }

    /**
     * Whether current user can see the entry point.
     *
     * @param int $courseid
     * @return bool
     */
    public function can_ask(int $courseid): bool {
        try {
            $this->require_ask($courseid);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ensure a caller cannot authorise a different user through this service.
     *
     * @param int $userid
     */
    private static function validate_current_user(int $userid): void {
        global $USER;
        if ((int) $USER->id !== $userid) {
            throw new \coding_exception('course_guard only authorises the current Moodle user.');
        }
    }
}
