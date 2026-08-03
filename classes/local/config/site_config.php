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
namespace block_courseaiguide\local\config;

defined('MOODLE_INTERNAL') || die();

/**
 * Validated site configuration access.
 */
final class site_config {
    /** @return string */
    public function endpoint(): string {
        return trim((string) get_config('block_courseaiguide', 'endpoint'));
    }

    /** @return string */
    public function model(): string {
        return trim((string) get_config('block_courseaiguide', 'model'));
    }

    /** @return string */
    public function apikey(): string {
        return (string) get_config('block_courseaiguide', 'apikey');
    }

    /** @return string */
    public function disclaimer(): string {
        return (string) get_config('block_courseaiguide', 'disclaimer');
    }

    /** @return int */
    public function retention_days(): int {
        $days = (int) get_config('block_courseaiguide', 'retentiondays');
        return $days <= 0 ? 0 : min(365, max(1, $days));
    }

    /** @return bool */
    public function statistics_enabled(): bool {
        return (bool) get_config('block_courseaiguide', 'statisticsenabled');
    }

    /** @return int */
    public function short_rate_limit(): int {
        return max(1, min(1000, (int) get_config('block_courseaiguide', 'ratelimitshort') ?: 10));
    }

    /** @return int */
    public function daily_rate_limit(): int {
        return max(1, min(10000, (int) get_config('block_courseaiguide', 'ratelimitday') ?: 100));
    }

    /**
     * Whether the provider settings are complete and structurally safe.
     *
     * @return bool
     */
    public function provider_ready(): bool {
        $parts = parse_url($this->endpoint());
        return $this->endpoint() !== ''
            && $this->model() !== ''
            && $this->apikey() !== ''
            && is_array($parts)
            && ($parts['scheme'] ?? '') === 'https'
            && empty($parts['user'])
            && empty($parts['pass'])
            && !empty($parts['host']);
    }
}
