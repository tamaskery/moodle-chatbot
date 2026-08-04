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
namespace block_courseaiguide\local\provider;

use block_courseaiguide\local\config\site_config;

/**
 * OpenAI-compatible provider using Moodle curl and URL security.
 */
final class openai_compatible_provider implements chat_provider_interface {
    /** Maximum accepted provider response size (one MiB). */
    private const MAX_RESPONSE_BYTES = 1048576;

    /** @var site_config Validated site configuration. */
    private $config;

    /**
     * Create an OpenAI-compatible provider.
     *
     * @param site_config|null $config Optional validated site configuration.
     */
    public function __construct(?site_config $config = null) {
        $this->config = $config ?? new site_config();
    }

    /** @inheritDoc */
    public function complete(chat_request $request): chat_response {
        if (!$this->config->provider_ready()) {
            throw new provider_exception();
        }
        $payload = (new chat_payload_builder())->build($this->config->model(), $request->messages);

        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT' => 30,
            // Never forward the bearer token to a redirect destination.
            'CURLOPT_FOLLOWLOCATION' => false,
            'CURLOPT_MAXREDIRS' => 0,
            'CURLOPT_MAXFILESIZE' => self::MAX_RESPONSE_BYTES,
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->config->apikey(),
            ],
        ];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $curl = new \curl([
                'securityhelper' => new \core\files\curl_security_helper(),
            ]);
            $raw = $curl->post($this->config->endpoint(), $payload, $options);
            $errno = (int) $curl->get_errno();
            $info = $curl->get_info();
            $status = is_array($info) ? (int) ($info['http_code'] ?? 0) : (int) ($info->http_code ?? 0);
            if (is_string($raw) && strlen($raw) > self::MAX_RESPONSE_BYTES) {
                throw new provider_exception('error:invalidresponse', 'response_too_large');
            }
            $transient = $errno !== 0 || $status === 429 || ($status >= 500 && $status <= 599);
            if ($errno === 0 && $status >= 200 && $status <= 299 && is_string($raw)) {
                $decoded = json_decode($raw, true, 32);
                $content = $decoded['choices'][0]['message']['content'] ?? null;
                if (!is_string($content) || strlen($content) > 50000) {
                    throw new provider_exception('error:invalidresponse', 'invalid_response');
                }
                return new chat_response($content);
            }
            if (!$transient || $attempt === 1) {
                throw new provider_exception('error:provider', $this->diagnostic($errno, $status));
            }
        }
        throw new provider_exception();
    }

    /**
     * Convert transport status into a safe category without response content.
     *
     * @param int $errno Curl error number.
     * @param int $status HTTP status code.
     * @return string
     */
    private function diagnostic(int $errno, int $status): string {
        if ($errno !== 0 || $status === 0) {
            return 'network';
        }
        if ($status === 400) {
            return 'request_rejected';
        }
        if ($status === 401) {
            return 'authentication';
        }
        if ($status === 403) {
            return 'permission';
        }
        if ($status === 404) {
            return 'model_or_endpoint';
        }
        if ($status === 429) {
            return 'rate_limit_or_quota';
        }
        if ($status >= 500 && $status <= 599) {
            return 'provider_5xx';
        }
        return 'provider_error';
    }
}
