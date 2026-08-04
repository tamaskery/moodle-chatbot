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
namespace block_courseaiguide;

use block_courseaiguide\local\config\site_config;

/** Tests for fail-closed site configuration. */
final class site_config_test extends \advanced_testcase {
    /** Provider defaults must be unusable. */
    public function test_provider_is_disabled_by_default(): void {
        $this->resetAfterTest();
        unset_config('endpoint', 'block_courseaiguide');
        unset_config('model', 'block_courseaiguide');
        unset_config('apikey', 'block_courseaiguide');
        $this->assertFalse((new site_config())->provider_ready());
    }

    /** Only a complete credential-free HTTPS URL is accepted. */
    public function test_provider_requires_safe_https_configuration(): void {
        $this->resetAfterTest();
        set_config('endpoint', 'http://provider.example/v1/chat/completions', 'block_courseaiguide');
        set_config('model', 'test-model', 'block_courseaiguide');
        set_config('apikey', 'fake-key', 'block_courseaiguide');
        $this->assertFalse((new site_config())->provider_ready());

        set_config('endpoint', 'https://user:pass@provider.example/v1/chat/completions', 'block_courseaiguide');
        $this->assertFalse((new site_config())->provider_ready());

        set_config('endpoint', "https://provider.example/v1/chat/\ncompletions", 'block_courseaiguide');
        $this->assertFalse((new site_config())->provider_ready());

        set_config('endpoint', 'https://provider.example/v1/chat/completions#redirect-target', 'block_courseaiguide');
        $this->assertFalse((new site_config())->provider_ready());

        set_config('endpoint', 'https://provider.example/v1/chat/completions', 'block_courseaiguide');
        $this->assertTrue((new site_config())->provider_ready());
    }

    /** Retention is disabled at zero and clamped to the approved ceiling. */
    public function test_retention_is_bounded(): void {
        $this->resetAfterTest();
        set_config('retentiondays', 0, 'block_courseaiguide');
        $this->assertSame(0, (new site_config())->retention_days());
        set_config('retentiondays', 999, 'block_courseaiguide');
        $this->assertSame(365, (new site_config())->retention_days());
    }
}
