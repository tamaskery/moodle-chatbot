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
namespace block_courseaiguide\local\provider;

/**
 * Builds model-compatible Chat Completions request payloads.
 */
final class chat_payload_builder {
    /**
     * Build the JSON request body.
     *
     * @param string $model Provider model identifier.
     * @param array $messages Chat Completions messages.
     * @return string JSON request body.
     */
    public function build(string $model, array $messages): string {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
        ];

        // GPT-5.6 defaults to reasoning and does not accept sampling controls
        // in every reasoning mode. Course Q&A is latency-sensitive, so use the
        // documented non-reasoning Chat Completions mode explicitly.
        if (preg_match('/^gpt-5\.6(?:-|$)/i', trim($model)) === 1) {
            $payload['reasoning_effort'] = 'none';
        } else {
            $payload['temperature'] = 0.1;
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || strlen($encoded) > 100000) {
            throw new provider_exception('error:invalidresponse', 'invalid_response');
        }
        return $encoded;
    }
}
