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
namespace block_courseaiguide\local\provider;

/**
 * Bounded provider request value object.
 */
final class chat_request {
    /** @var array Provider request messages. */
    public $messages;
    /** @var string Non-sensitive request identifier. */
    public $requestid;

    /**
     * Constructor.
     *
     * @param array $messages
     * @param string $requestid
     */
    public function __construct(array $messages, string $requestid) {
        $this->messages = $messages;
        $this->requestid = $requestid;
    }
}
