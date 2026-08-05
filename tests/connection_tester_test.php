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
namespace block_courseaiguide;

use block_courseaiguide\local\provider\chat_response;
use block_courseaiguide\local\provider\connection_tester;
use block_courseaiguide\local\provider\provider_exception;

/**
 * Provider connection response-contract tests.
 *
 * @covers \block_courseaiguide\local\provider\connection_tester
 */
final class connection_tester_test extends \advanced_testcase {
    /**
     * A valid production-shaped JSON result is accepted.
     */
    public function test_valid_response_is_accepted(): void {
        $response = new chat_response('{"found":false,"answer":"","sourceids":[]}');
        (new connection_tester())->validate_response($response);
        $this->addToAssertionCount(1);
    }

    /**
     * Malformed JSON cannot produce a successful connection result.
     */
    public function test_invalid_json_is_rejected(): void {
        $this->expectException(provider_exception::class);
        (new connection_tester())->validate_response(new chat_response('not json'));
    }

    /**
     * Source identifiers must use the production integer contract.
     */
    public function test_non_integer_source_identifier_is_rejected(): void {
        $this->expectException(provider_exception::class);
        $response = new chat_response('{"found":true,"answer":"ok","sourceids":["1"]}');
        (new connection_tester())->validate_response($response);
    }
}
