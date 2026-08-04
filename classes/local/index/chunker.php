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
namespace block_courseaiguide\local\index;

/**
 * Stable Unicode-aware word chunker.
 */
final class chunker {
    /** @var int Target words per chunk. */
    private const WORDS = 800;
    /** @var int Overlapping words between chunks. */
    private const OVERLAP = 100;
    /** @var int Maximum chunks generated from one source. */
    private const MAXCHUNKS = 50;

    /**
     * Split bounded plain text into overlapping chunks.
     *
     * @param string $text
     * @return array
     */
    public function split(string $text): array {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return [];
        }
        $chunks = [];
        $offset = 0;
        $step = self::WORDS - self::OVERLAP;
        while ($offset < count($words) && count($chunks) < self::MAXCHUNKS) {
            $slice = array_slice($words, $offset, self::WORDS);
            $content = implode(' ', $slice);
            if ($content !== '') {
                $chunks[] = $content;
            }
            if (count($slice) < self::WORDS) {
                break;
            }
            $offset += $step;
        }
        return $chunks;
    }
}
