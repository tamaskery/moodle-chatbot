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
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\local\chat;

use block_courseaiguide\local\provider\chat_response;
use block_courseaiguide\local\provider\provider_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates untrusted provider output and server-maps citations.
 */
final class answer_validator {
    /**
     * Validate a provider response.
     *
     * @param chat_response $response
     * @param int $courseid
     * @param int $userid
     * @param array $chunks
     * @return array
     */
    public function validate(chat_response $response, int $courseid, int $userid, array $chunks): array {
        $decoded = json_decode($response->content, true, 16);
        if (!is_array($decoded) || !array_key_exists('found', $decoded) || !is_bool($decoded['found'])) {
            throw new provider_exception('error:invalidresponse');
        }
        if (!$decoded['found']) {
            return [
                'mode' => 'notfound',
                'answer' => get_string('notfound', 'block_courseaiguide'),
                'facts' => [],
                'sources' => [],
            ];
        }
        if (!is_string($decoded['answer'] ?? null) || !is_array($decoded['sourceids'] ?? null)) {
            throw new provider_exception('error:invalidresponse');
        }
        $answer = clean_param($decoded['answer'], PARAM_TEXT);
        $answer = preg_replace('~(?:https?://|www\.)\S+~iu', '', $answer) ?? '';
        $answer = trim(\core_text::substr($answer, 0, 6000));
        if ($answer === '') {
            throw new provider_exception('error:invalidresponse');
        }
        $allowedids = array_map(static function(array $chunk): int {
            return (int) $chunk['source']['id'];
        }, $chunks);
        $sources = (new citation_mapper())->map($courseid, $userid, $allowedids, $decoded['sourceids']);
        if (!$sources) {
            return [
                'mode' => 'notfound',
                'answer' => get_string('notfound', 'block_courseaiguide'),
                'facts' => [],
                'sources' => [],
            ];
        }
        return [
            'mode' => 'rag',
            'answer' => $answer,
            'facts' => [],
            'sources' => $sources,
        ];
    }
}
