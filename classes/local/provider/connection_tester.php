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

use block_courseaiguide\local\config\site_config;
use block_courseaiguide\local\rate\site_circuit_breaker;

/**
 * Administrator-only provider compatibility test service.
 */
final class connection_tester {
    /**
     * Send one fixed request using the saved site configuration.
     *
     * @return array Non-sensitive connection result.
     */
    public function test(): array {
        $config = new site_config();
        if (!$config->provider_ready()) {
            throw new provider_exception('error:providernotready', 'model_or_endpoint');
        }

        try {
            (new site_circuit_breaker($config))->reserve();
        } catch (\moodle_exception $e) {
            throw new provider_exception('error:provider', 'site_rate_control');
        }

        $requestid = bin2hex(random_bytes(8));
        $request = new chat_request([
            [
                'role' => 'system',
                'content' => 'This is a connection test with no course data. Return only a JSON object with '
                    . 'found (boolean), answer (string), and sourceids (array of integers).',
            ],
            [
                'role' => 'user',
                'content' => 'Return found=false, answer="", and sourceids=[].',
            ],
        ], $requestid);

        $started = microtime(true);
        $response = (new provider_factory())->create()->complete($request);
        $this->validate_response($response);

        $parts = parse_url($config->endpoint());
        return [
            'host' => clean_param((string) ($parts['host'] ?? ''), PARAM_HOST),
            'model' => clean_param($config->model(), PARAM_TEXT),
            'latencyms' => (int) round((microtime(true) - $started) * 1000),
            'requestid' => $requestid,
        ];
    }

    /**
     * Confirm that the model can return the production JSON contract.
     *
     * @param chat_response $response Untrusted provider response.
     */
    public function validate_response(chat_response $response): void {
        $decoded = json_decode($response->content, true, 16);
        if (!is_array($decoded) || !array_key_exists('found', $decoded) || !is_bool($decoded['found'])) {
            throw new provider_exception('error:invalidresponse', 'invalid_response');
        }
        if (!is_string($decoded['answer'] ?? null) || !is_array($decoded['sourceids'] ?? null)) {
            throw new provider_exception('error:invalidresponse', 'invalid_response');
        }
        foreach ($decoded['sourceids'] as $sourceid) {
            if (!is_int($sourceid)) {
                throw new provider_exception('error:invalidresponse', 'invalid_response');
            }
        }
    }
}
