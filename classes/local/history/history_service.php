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
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\local\history;

use block_courseaiguide\local\config\site_config;

defined('MOODLE_INTERNAL') || die();

/**
 * Optional three-gate participant-owned conversation storage.
 */
final class history_service {
    /**
     * Store a turn only after all retention gates pass.
     *
     * @param \stdClass $courseconfig
     * @param int $userid
     * @param bool $optedin
     * @param string $conversationtoken
     * @param string $question
     * @param string $answer
     * @param string $requestid
     * @return string Empty when no data is stored.
     */
    public function store_turn(
        \stdClass $courseconfig,
        int $userid,
        bool $optedin,
        string $conversationtoken,
        string $question,
        string $answer,
        string $requestid
    ): string {
        global $DB;
        $days = (new site_config())->retention_days();
        if (!$optedin || !$days || empty($courseconfig->historyenabled)) {
            return '';
        }
        $now = time();
        $expires = $now + ($days * DAYSECS);
        $transaction = $DB->start_delegated_transaction();
        $conversation = null;
        if ($conversationtoken !== '') {
            $conversation = $DB->get_record_select('block_courseaiguide_conv',
                'publictoken = :token AND courseid = :courseid AND userid = :userid AND expiresat > :now', [
                'token' => $conversationtoken,
                'courseid' => $courseconfig->courseid,
                'userid' => $userid,
                'now' => $now,
            ]);
        }
        if (!$conversation) {
            $conversation = (object) [
                'courseid' => $courseconfig->courseid,
                'userid' => $userid,
                'publictoken' => hash('sha256', random_bytes(32)),
                'timeoptedin' => $now,
                'expiresat' => $expires,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $conversation->id = $DB->insert_record('block_courseaiguide_conv', $conversation);
        } else {
            $conversation->expiresat = $expires;
            $conversation->timemodified = $now;
            $DB->update_record('block_courseaiguide_conv', $conversation);
        }
        foreach (['user' => $question, 'assistant' => $answer] as $role => $content) {
            $DB->insert_record('block_courseaiguide_msg', (object) [
                'conversationid' => $conversation->id,
                'courseid' => $courseconfig->courseid,
                'userid' => $userid,
                'role' => $role,
                'content' => \core_text::substr(clean_param($content, PARAM_TEXT), 0, 10000),
                'requestid' => $requestid,
                'expiresat' => $expires,
                'timecreated' => $now,
            ]);
        }
        $transaction->allow_commit();
        return (string) $conversation->publictoken;
    }

    /**
     * List owned conversations without message text.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    public function list_owned(int $courseid, int $userid): array {
        global $DB;
        return array_values($DB->get_records_select('block_courseaiguide_conv',
            'courseid = :courseid AND userid = :userid AND expiresat > :now', [
                'courseid' => $courseid,
                'userid' => $userid,
                'now' => time(),
            ], 'timemodified DESC', 'publictoken,timecreated,timemodified,expiresat'));
    }

    /**
     * Get owned messages.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $token
     * @return array
     */
    public function get_owned(int $courseid, int $userid, string $token): array {
        global $DB;
        $conversation = $DB->get_record_select('block_courseaiguide_conv',
            'courseid = :courseid AND userid = :userid AND publictoken = :token AND expiresat > :now', [
                'courseid' => $courseid,
                'userid' => $userid,
                'token' => $token,
                'now' => time(),
            ]);
        if (!$conversation) {
            return [];
        }
        return array_values($DB->get_records_select('block_courseaiguide_msg',
            'courseid = :courseid AND userid = :userid AND conversationid = :conversationid AND expiresat > :now', [
                'courseid' => $courseid,
                'userid' => $userid,
                'conversationid' => $conversation->id,
                'now' => time(),
            ], 'timecreated ASC', 'role,content,timecreated'));
    }

    /**
     * Delete one owned conversation.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $token
     */
    public function delete_owned(int $courseid, int $userid, string $token): void {
        global $DB;
        $conversation = $DB->get_record('block_courseaiguide_conv', [
            'courseid' => $courseid,
            'userid' => $userid,
            'publictoken' => $token,
        ]);
        if (!$conversation) {
            return;
        }
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('block_courseaiguide_msg', ['conversationid' => $conversation->id]);
        $DB->delete_records('block_courseaiguide_conv', ['id' => $conversation->id]);
        $transaction->allow_commit();
    }
}
