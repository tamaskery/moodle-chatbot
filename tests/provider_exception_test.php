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

use block_courseaiguide\local\provider\provider_exception;

/**
 * Tests for safe provider diagnostics.
 *
 * @covers \block_courseaiguide\local\provider\provider_exception
 */
final class provider_exception_test extends \advanced_testcase {
    /**
     * Unknown diagnostic values cannot propagate to administrators.
     */
    public function test_diagnostic_is_allowlisted(): void {
        $exception = new provider_exception('error:provider', 'raw provider response');
        $this->assertSame('provider_error', $exception->diagnostic());
    }

    /**
     * Safe diagnostic categories remain available.
     */
    public function test_known_diagnostic_is_retained(): void {
        $exception = new provider_exception('error:provider', 'authentication');
        $this->assertSame('authentication', $exception->diagnostic());
    }
}
