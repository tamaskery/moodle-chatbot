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
defined('MOODLE_INTERNAL') || die();

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

    return true;
}
