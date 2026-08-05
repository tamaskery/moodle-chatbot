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
namespace block_courseaiguide\local\config;

/**
 * Validated site configuration access.
 */
final class site_config {
    /**
     * Return the configured provider endpoint.
     *
     * @return string Provider endpoint URL.
     */
    public function endpoint(): string {
        return trim((string) get_config('block_courseaiguide', 'endpoint'));
    }

    /**
     * Return the configured provider model.
     *
     * @return string Provider model name.
     */
    public function model(): string {
        return trim((string) get_config('block_courseaiguide', 'model'));
    }

    /**
     * Return the configured provider API key.
     *
     * @return string Provider API key.
     */
    public function apikey(): string {
        return (string) get_config('block_courseaiguide', 'apikey');
    }

    /**
     * Return the approved participant disclaimer.
     *
     * @return string Disclaimer text.
     */
    public function disclaimer(): string {
        return (string) get_config('block_courseaiguide', 'disclaimer');
    }

    /**
     * Return the bounded conversation-retention period.
     *
     * @return int Retention period in days, or zero when disabled.
     */
    public function retention_days(): int {
        $days = (int) get_config('block_courseaiguide', 'retentiondays');
        return $days <= 0 ? 0 : min(365, max(1, $days));
    }

    /**
     * Return the administrator-approved diagnostic retention window.
     *
     * @return int Retention in hours, or zero when incident capture is disabled.
     */
    public function diagnostic_retention_hours(): int {
        $hours = (int) get_config('block_courseaiguide', 'diagnosticretentionhours');
        return $hours <= 0 ? 0 : min(168, max(1, $hours));
    }

    /**
     * Return whether aggregate statistics are enabled.
     *
     * @return bool Whether aggregate statistics are enabled.
     */
    public function statistics_enabled(): bool {
        return (bool) get_config('block_courseaiguide', 'statisticsenabled');
    }

    /**
     * Return the short-window request limit.
     *
     * @return int Requests allowed in the short window.
     */
    public function short_rate_limit(): int {
        return max(1, min(1000, (int) get_config('block_courseaiguide', 'ratelimitshort') ?: 10));
    }

    /**
     * Return the daily request limit.
     *
     * @return int Requests allowed each day.
     */
    public function daily_rate_limit(): int {
        return max(1, min(10000, (int) get_config('block_courseaiguide', 'ratelimitday') ?: 100));
    }

    /**
     * Return the site-wide daily logical provider-call ceiling.
     *
     * @return int Provider calls allowed across the whole site each UTC day.
     */
    public function site_daily_provider_limit(): int {
        $limit = (int) get_config('block_courseaiguide', 'siteprovidercalllimit') ?: 1000;
        return max(1, min(1000000, $limit));
    }

    /**
     * Whether the provider settings are complete and structurally safe.
     *
     * @return bool
     */
    public function provider_ready(): bool {
        $endpoint = $this->endpoint();
        $parts = parse_url($endpoint);
        return $endpoint !== ''
            && $this->model() !== ''
            && $this->apikey() !== ''
            && !preg_match('/[\x00-\x20\x7f]/', $endpoint)
            && is_array($parts)
            && ($parts['scheme'] ?? '') === 'https'
            && empty($parts['user'])
            && empty($parts['pass'])
            && empty($parts['fragment'])
            && !empty($parts['host']);
    }
}
