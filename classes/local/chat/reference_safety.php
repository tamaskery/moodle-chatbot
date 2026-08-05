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
namespace block_courseaiguide\local\chat;

/**
 * Conservatively excludes reference text that resembles prompt injection.
 *
 * This deterministic layer reduces exposure to common attacks before a
 * provider sees course text. It complements, but does not replace, access
 * checks, the provider system policy, output validation, and empirical tests.
 */
final class reference_safety {
    /**
     * Remove chunks that contain likely prompt-injection instructions.
     *
     * @param array $chunks Retrieved chunks.
     * @return array Safe chunks with sequential array keys.
     */
    public static function filter(array $chunks): array {
        return array_values(array_filter($chunks, static function (array $chunk): bool {
            return !self::contains_prompt_injection((string) ($chunk['content'] ?? ''));
        }));
    }

    /**
     * Detect a combination of common prompt-injection signals.
     *
     * A strong instruction override is sufficient by itself. Weaker signals
     * need to occur together so ordinary security guidance is not discarded
     * merely because it mentions an API key or system prompt.
     *
     * @param string $text Reference text.
     * @return bool True when the text should not be sent to a provider.
     */
    public static function contains_prompt_injection(string $text): bool {
        $normalised = \core_text::strtolower(clean_param($text, PARAM_TEXT));
        if ($normalised === '') {
            return false;
        }

        $signals = [
            [2, '/\bignore\s+(?:all\s+)?(?:previous|prior|above|earlier)\s+(?:instructions?|rules?|messages?)\b/u'],
            [1, '/(?:^|[\r\n.!?]\s*)(?:system|developer|assistant)\s*:/u'],
            [
                1,
                '/\b(?:reveal|show|print|return|expose)\b.{0,80}\b(?:system|developer|hidden|internal)\b'
                    . '.{0,40}\b(?:prompt|instructions?|messages?)\b/u',
            ],
            [1, '/\b(?:api|secret|access)\s*(?:key|token|credentials?|password)\b/u'],
            [1, '/\bdo\s+not\s+(?:cite|mention|disclose|attribute)\b/u'],
            [1, '/\b(?:treat|regard|use)\b.{0,60}\b(?:authoritative|system|instruction)\b/u'],
        ];

        $score = 0;
        foreach ($signals as [$weight, $pattern]) {
            if (preg_match($pattern, $normalised)) {
                $score += $weight;
                if ($score >= 2) {
                    return true;
                }
            }
        }
        return false;
    }
}
