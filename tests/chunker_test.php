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
namespace block_courseaiguide;

use block_courseaiguide\local\index\chunker;

defined('MOODLE_INTERNAL') || die();

/** Tests for deterministic content chunking. */
final class chunker_test extends \advanced_testcase {
    /** Short text should form one unchanged chunk. */
    public function test_short_text_is_one_chunk(): void {
        $chunks = (new chunker())->split('One two three');
        $this->assertSame(['One two three'], $chunks);
    }

    /** Long text should use the configured 100-word overlap. */
    public function test_long_text_has_expected_overlap(): void {
        $words = [];
        for ($index = 0; $index < 900; $index++) {
            $words[] = 'word' . $index;
        }
        $chunks = (new chunker())->split(implode(' ', $words));
        $this->assertCount(2, $chunks);
        $this->assertStringEndsWith('word799', $chunks[0]);
        $this->assertStringStartsWith('word700', $chunks[1]);
    }

    /** Empty text should produce no index rows. */
    public function test_empty_text_has_no_chunks(): void {
        $this->assertSame([], (new chunker())->split("  \n  "));
    }
}
