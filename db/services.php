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

$functions = [
    'block_courseaiguide_ask' => [
        'classname' => '\\block_courseaiguide\\external\\ask',
        'description' => 'Ask a course-scoped question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:ask',
    ],
    'block_courseaiguide_get_chat_config' => [
        'classname' => '\\block_courseaiguide\\external\\get_chat_config',
        'description' => 'Get safe participant chat configuration.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:ask',
    ],
    'block_courseaiguide_get_index_status' => [
        'classname' => '\\block_courseaiguide\\external\\get_index_status',
        'description' => 'Get manager-visible index status.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:manage',
    ],
    'block_courseaiguide_request_reindex' => [
        'classname' => '\\block_courseaiguide\\external\\request_reindex',
        'description' => 'Queue course re-indexing.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:manage',
    ],
    'block_courseaiguide_list_conversations' => [
        'classname' => '\\block_courseaiguide\\external\\list_conversations',
        'description' => 'List current participant-owned retained conversations.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:ask',
    ],
    'block_courseaiguide_get_conversation' => [
        'classname' => '\\block_courseaiguide\\external\\get_conversation',
        'description' => 'Get current participant-owned retained conversation.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:ask',
    ],
    'block_courseaiguide_delete_conversation' => [
        'classname' => '\\block_courseaiguide\\external\\delete_conversation',
        'description' => 'Delete current participant-owned retained conversation.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/courseaiguide:ask',
    ],
];
