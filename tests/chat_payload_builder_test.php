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
namespace block_courseaiguide;

use block_courseaiguide\local\provider\chat_payload_builder;

/**
 * Tests for provider request compatibility.
 *
 * @covers \block_courseaiguide\local\provider\chat_payload_builder
 */
final class chat_payload_builder_test extends \advanced_testcase {
    /**
     * GPT-5.6 uses the supported low-latency reasoning mode.
     */
    public function test_gpt_5_6_payload_uses_none_reasoning(): void {
        $messages = [['role' => 'user', 'content' => 'Question']];
        $payload = json_decode((new chat_payload_builder())->build('gpt-5.6-luna', $messages), true);

        $this->assertSame('gpt-5.6-luna', $payload['model']);
        $this->assertSame($messages, $payload['messages']);
        $this->assertSame('none', $payload['reasoning_effort']);
        $this->assertArrayNotHasKey('temperature', $payload);
        $this->assertSame(['type' => 'json_object'], $payload['response_format']);
    }

    /**
     * Other compatible providers retain the existing sampling setting.
     */
    public function test_other_model_payload_retains_temperature(): void {
        $payload = json_decode((new chat_payload_builder())->build('gpt-4.1-mini', []), true);

        $this->assertSame(0.1, $payload['temperature']);
        $this->assertArrayNotHasKey('reasoning_effort', $payload);
    }
}
