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
namespace block_courseaiguide\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\content_writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle Privacy API provider.
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /** @inheritDoc */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_courseaiguide_conv', [
            'courseid' => 'privacy:metadata:courseid',
            'userid' => 'privacy:metadata:userid',
            'publictoken' => 'privacy:metadata:publictoken',
            'timeoptedin' => 'privacy:metadata:timeoptedin',
            'expiresat' => 'privacy:metadata:expiresat',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:conversation');
        $collection->add_database_table('block_courseaiguide_msg', [
            'conversationid' => 'privacy:metadata:conversationid',
            'courseid' => 'privacy:metadata:courseid',
            'userid' => 'privacy:metadata:userid',
            'role' => 'privacy:metadata:role',
            'content' => 'privacy:metadata:content',
            'requestid' => 'privacy:metadata:requestid',
            'expiresat' => 'privacy:metadata:expiresat',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:message');
        $collection->add_database_table('block_courseaiguide_rate', [
            'courseid' => 'privacy:metadata:courseid',
            'userid' => 'privacy:metadata:userid',
            'windowtype' => 'privacy:metadata:windowtype',
            'windowstart' => 'privacy:metadata:windowstart',
            'windowend' => 'privacy:metadata:windowend',
            'requestcount' => 'privacy:metadata:requestcount',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:rate');
        $collection->add_external_location_link('configured_ai_provider', [
            'question' => 'privacy:metadata:content',
            'coursefacts' => 'privacy:metadata:provider',
            'courseexcerpts' => 'privacy:metadata:provider',
        ], 'privacy:metadata:provider');
        return $collection;
    }

    /** @inheritDoc */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        foreach (['block_courseaiguide_conv', 'block_courseaiguide_msg', 'block_courseaiguide_rate'] as $table) {
            $sql = "SELECT ctx.id
                      FROM {context} ctx
                      JOIN {{$table}} d ON d.courseid = ctx.instanceid
                     WHERE ctx.contextlevel = :contextlevel AND d.userid = :userid";
            $contextlist->add_from_sql($sql, $params);
        }
        return $contextlist;
    }

    /** @inheritDoc */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $courseid = (int) $context->instanceid;
            $conversations = $DB->get_records('block_courseaiguide_conv', [
                'courseid' => $courseid,
                'userid' => $userid,
            ], 'timecreated ASC');
            foreach ($conversations as $conversation) {
                $messages = array_values($DB->get_records('block_courseaiguide_msg', [
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'conversationid' => $conversation->id,
                ], 'timecreated ASC', 'role,content,timecreated,expiresat'));
                content_writer::with_context($context)->export_data([
                    get_string('privacy:path', 'block_courseaiguide'),
                    $conversation->publictoken,
                ], (object) [
                    'optedintime' => transform::datetime($conversation->timeoptedin),
                    'expiry' => transform::datetime($conversation->expiresat),
                    'messages' => $messages,
                ]);
            }
            $rates = array_values($DB->get_records('block_courseaiguide_rate', [
                'courseid' => $courseid,
                'userid' => $userid,
            ], 'windowstart ASC', 'windowtype,windowstart,windowend,requestcount'));
            if ($rates) {
                content_writer::with_context($context)->export_related_data([
                    get_string('privacy:path', 'block_courseaiguide'),
                ], 'rate_limits', (object) ['windows' => $rates]);
            }
        }
    }

    /** @inheritDoc */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        self::delete_for_course((int) $context->instanceid, null);
    }

    /** @inheritDoc */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                self::delete_for_course((int) $context->instanceid, $userid);
            }
        }
    }

    /** @inheritDoc */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $params = ['courseid' => $context->instanceid];
        $sql = 'SELECT userid FROM {block_courseaiguide_conv} WHERE courseid = :courseid1
                UNION SELECT userid FROM {block_courseaiguide_msg} WHERE courseid = :courseid2
                UNION SELECT userid FROM {block_courseaiguide_rate} WHERE courseid = :courseid3';
        $userlist->add_from_sql('userid', $sql, [
            'courseid1' => $params['courseid'],
            'courseid2' => $params['courseid'],
            'courseid3' => $params['courseid'],
        ]);
    }

    /** @inheritDoc */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            self::delete_for_course((int) $context->instanceid, (int) $userid);
        }
    }

    /**
     * Delete personal records for a course and optional user.
     *
     * @param int $courseid
     * @param int|null $userid
     */
    private static function delete_for_course(int $courseid, ?int $userid): void {
        global $DB;
        $conditions = ['courseid' => $courseid];
        if ($userid !== null) {
            $conditions['userid'] = $userid;
        }
        $DB->delete_records('block_courseaiguide_msg', $conditions);
        $DB->delete_records('block_courseaiguide_conv', $conditions);
        $DB->delete_records('block_courseaiguide_rate', $conditions);
    }
}
