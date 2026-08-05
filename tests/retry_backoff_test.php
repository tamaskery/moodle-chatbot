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

use block_courseaiguide\local\provider\retry_backoff;

/**
 * Retry backoff tests.
 *
 * @covers \block_courseaiguide\local\provider\retry_backoff
 */
final class retry_backoff_test extends \advanced_testcase {
    /**
     * Jitter always remains within the documented short delay.
     */
    public function test_delay_is_bounded(): void {
        $backoff = new retry_backoff();
        for ($sample = 0; $sample < 100; $sample++) {
            $delay = $backoff->delay_milliseconds();
            $this->assertGreaterThanOrEqual(retry_backoff::MIN_MILLISECONDS, $delay);
            $this->assertLessThanOrEqual(retry_backoff::MAX_MILLISECONDS, $delay);
        }
    }
}
