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

use block_courseaiguide\local\rate\rate_limiter;

/**
 * Tests for database-backed abuse controls.
 *
 * @covers \block_courseaiguide\local\rate\rate_limiter
 */
final class rate_limiter_test extends \advanced_testcase {
    /**
     * Per-user/course short limit must fail closed after the configured count.
     */
    public function test_short_window_limit(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        set_config('ratelimitshort', 2, 'block_courseaiguide');
        set_config('ratelimitday', 100, 'block_courseaiguide');
        $limiter = new rate_limiter();
        $limiter->consume($course->id, $user->id);
        $limiter->consume($course->id, $user->id);
        $this->expectException(\moodle_exception::class);
        $limiter->consume($course->id, $user->id);
    }
}
