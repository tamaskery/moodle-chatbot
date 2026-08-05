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
/**
 * Upgrade hook for block_courseaiguide.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_courseaiguide_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2026080406) {
        $table = new xmldb_table('block_courseaiguide_site');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('daystart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('providercalls', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'daystart');
        $table->add_field('trippedat', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'providercalls');
        $table->add_field('notifiedat', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'trippedat');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'notifiedat');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('dayuniq', XMLDB_INDEX_UNIQUE, ['daystart']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2026080406, 'courseaiguide');
    }

    if ($oldversion < 2026080500) {
        $coursetable = new xmldb_table('block_courseaiguide_course');
        $diagnosticuntil = new xmldb_field(
            'diagnosticuntil',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'historyenabled'
        );
        if (!$dbman->field_exists($coursetable, $diagnosticuntil)) {
            $dbman->add_field($coursetable, $diagnosticuntil);
        }

        $table = new xmldb_table('block_courseaiguide_diag');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'courseid');
        $table->add_field('requestid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null, 'userid');
        $table->add_field('mode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, 'requestid');
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'mode');
        $table->add_field('answer', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'question');
        $table->add_field('factsjson', XMLDB_TYPE_TEXT, null, null, null, null, null, 'answer');
        $table->add_field('sourcesjson', XMLDB_TYPE_TEXT, null, null, null, null, null, 'factsjson');
        $table->add_field('referencejson', XMLDB_TYPE_TEXT, null, null, null, null, null, 'sourcesjson');
        $table->add_field('guidance', XMLDB_TYPE_TEXT, null, null, null, null, null, 'referencejson');
        $table->add_field('model', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'guidance');
        $table->add_field('providerhost', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'model');
        $table->add_field('diagnostic', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'providerhost');
        $table->add_field('pluginversion', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'diagnostic');
        $table->add_field('consentat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'pluginversion');
        $table->add_field('expiresat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'consentat');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'expiresat');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('coursefk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('userfk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('requestuniq', XMLDB_INDEX_UNIQUE, ['requestid']);
        $table->add_index('courseexpiryidx', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'expiresat']);
        $table->add_index('owneridx', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid', 'timecreated']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2026080500, 'courseaiguide');
    }

    return true;
}
