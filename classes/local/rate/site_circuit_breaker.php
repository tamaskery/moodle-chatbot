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
namespace block_courseaiguide\local\rate;

use block_courseaiguide\local\config\site_config;

/**
 * Atomic site-wide daily provider-call circuit breaker.
 */
final class site_circuit_breaker {
    /** @var site_config Validated site configuration. */
    private $config;

    /**
     * Create the circuit breaker.
     *
     * @param site_config|null $config Optional site configuration for tests.
     */
    public function __construct(?site_config $config = null) {
        $this->config = $config ?? new site_config();
    }

    /**
     * Whether the provider is paused for the current UTC day.
     *
     * @param int|null $now Optional timestamp for deterministic tests.
     * @return bool
     */
    public function is_open(?int $now = null): bool {
        $status = $this->status($now);
        return $status['open'];
    }

    /**
     * Return non-sensitive status for administrators and access guards.
     *
     * @param int|null $now Optional timestamp for deterministic tests.
     * @return array
     */
    public function status(?int $now = null): array {
        global $DB;

        $now = $now ?? time();
        $daystart = self::day_start($now);
        $calls = (int) $DB->get_field('block_courseaiguide_site', 'providercalls', ['daystart' => $daystart]);
        $limit = $this->config->site_daily_provider_limit();
        return [
            'daystart' => $daystart,
            'resetat' => $daystart + DAYSECS,
            'calls' => $calls,
            'limit' => $limit,
            'open' => $calls >= $limit,
        ];
    }

    /**
     * Atomically reserve one logical external provider call.
     *
     * Failed calls remain counted because they still consume provider and
     * infrastructure capacity. Deterministic and not-found answers do not call
     * this method and therefore do not consume the site budget.
     *
     * @param int|null $now Optional timestamp for deterministic tests.
     */
    public function reserve(?int $now = null): void {
        global $DB;

        $now = $now ?? time();
        $daystart = self::day_start($now);
        $limit = $this->config->site_daily_provider_limit();
        $factory = \core\lock\lock_config::get_lock_factory('block_courseaiguide_sitebudget');
        $lock = $factory->get_lock((string) $daystart, 2);
        if (!$lock) {
            throw new \moodle_exception('error:rateunavailable', 'block_courseaiguide');
        }

        $notify = false;
        try {
            $record = $DB->get_record('block_courseaiguide_site', ['daystart' => $daystart]);
            if (!$record) {
                $record = (object) [
                    'daystart' => $daystart,
                    'providercalls' => 0,
                    'trippedat' => null,
                    'notifiedat' => null,
                    'timemodified' => $now,
                ];
                $record->id = $DB->insert_record('block_courseaiguide_site', $record);
            }
            if ((int) $record->providercalls >= $limit) {
                throw $this->limit_exception($daystart);
            }

            // Raising the ceiling re-arms notification for the new threshold.
            if (!empty($record->trippedat)) {
                $record->trippedat = null;
                $record->notifiedat = null;
            }

            $record->providercalls = (int) $record->providercalls + 1;
            $record->timemodified = $now;
            if ((int) $record->providercalls >= $limit && empty($record->trippedat)) {
                $record->trippedat = $now;
                $record->notifiedat = $now;
                $notify = true;
            }
            $DB->update_record('block_courseaiguide_site', $record);
        } finally {
            $lock->release();
        }

        if ($notify) {
            $this->notify_admins($limit, $daystart + DAYSECS);
        }
    }

    /**
     * Return the UTC start of a fixed daily window.
     *
     * @param int $timestamp Timestamp.
     * @return int
     */
    private static function day_start(int $timestamp): int {
        return intdiv($timestamp, DAYSECS) * DAYSECS;
    }

    /**
     * Build the participant-safe exception for an open circuit.
     *
     * @param int $daystart UTC day start.
     * @return \moodle_exception
     */
    private function limit_exception(int $daystart): \moodle_exception {
        return new \moodle_exception(
            'error:sitecircuitbreaker',
            'block_courseaiguide',
            '',
            userdate($daystart + DAYSECS)
        );
    }

    /**
     * Send one notification to each site administrator when the circuit trips.
     *
     * Notification delivery must never turn a permitted provider call into a
     * user-visible failure. The persistent settings warning remains available
     * if a message processor is unavailable.
     *
     * @param int $limit Configured logical call limit.
     * @param int $resetat Reset timestamp.
     */
    private function notify_admins(int $limit, int $resetat): void {
        $data = (object) [
            'limit' => $limit,
            'reset' => userdate($resetat),
        ];
        foreach (get_admins() as $admin) {
            try {
                $message = new \core\message\message();
                $message->component = 'block_courseaiguide';
                $message->name = 'sitecircuitbreaker';
                $message->userfrom = \core_user::get_noreply_user();
                $message->userto = $admin;
                $message->subject = get_string('notification:sitecircuitbreaker_subject', 'block_courseaiguide');
                $message->fullmessage = get_string('notification:sitecircuitbreaker_body', 'block_courseaiguide', $data);
                $message->fullmessageformat = FORMAT_PLAIN;
                $message->fullmessagehtml = '';
                $message->smallmessage = get_string('notification:sitecircuitbreaker_short', 'block_courseaiguide');
                $message->notification = 1;
                $message->contexturl = (new \moodle_url('/admin/settings.php', [
                    'section' => 'blocksettingcourseaiguide',
                ]))->out(false);
                $message->contexturlname = get_string('pluginname', 'block_courseaiguide');
                message_send($message);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }
}
